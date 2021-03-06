<?php
require_once DIR_BASE . '/libraries/php-mf2/Mf2/Parser.php';
require_once DIR_BASE . '/libraries/php-comments/src/indieweb/comments.php';
require_once DIR_BASE . '/libraries/cassis/cassis-loader.php';
require_once DIR_BASE . '/libraries/mention-client-php/src/IndieWeb/MentionClient.php';
require_once DIR_BASE . '/libraries/php-mf2-shim/Mf2/functions.php';
require_once DIR_BASE . '/libraries/php-mf2-shim/Mf2/Shim/Twitter.php';
//require_once DIR_BASE . '/libraries/php-mf2-shim/Mf2/Shim/Facebook.php';

class ControllerWebmentionQueue extends Controller {
    public function index()
    {
        if (!isset($this->request->get['id'])) {
            header('HTTP/1.1 400 Bad Request');
            exit();
        } else {
            $this->load->model('webmention/queue');
            $entry = $this->model_webmention_queue->getEntry($this->request->get['id']);
            if ($entry) {
                header('Webmention-Status: ' . $entry['status_code']);

                if ($entry['status'] == 'accepted') {
                    $this->response->setOutput('This webmention has been accepted is awaiting moderator approval.');

                } elseif ($entry['status'] == 'OK') {
                    $this->response->setOutput('This webmention has been accepted and approved.');

                } elseif ($entry['status'] == 'queued') {
                    $this->response->setOutput('This webmention is in the process queue.');

                } else {
                    $this->response->setOutput('This webmention processing failed because: ' . $entry['status']);

                }

            } else {
                header('HTTP/1.1 404 Not Found');
                exit();
            }

        }
    }


    public function sender()
    {

        $this->load->model('webmention/send_queue');
        $post_id = $this->model_webmention_send_queue->getNext();

        while ($post_id) {
            $this->sendWebmention($post_id);

            $post_id = $this->model_webmention_send_queue->getNext();
        } //end while
    }


    public function sendWebmention($post_id, $old_text = '')
    {

        $this->load->model('blog/post');
        $post = $this->model_blog_post->getPost($post_id);

        $this->load->model('blog/category');
        $categories = $this->model_blog_category->getCategoriesForPost($post_id);

        $webmention_text = '<a href="' . $post['in-reply-to'] . '">ReplyTo</a>';

        if ($post['like-of']) {
            $webmetnion_text = '<a href="' . $post['like-of'] . '"></a>';
        }
        if ($post['bookmark-of']) {
            $webmetnion_text = '<a href="' . $post['bookmark-of'] . '"></a>';
        }

        foreach ($categories as $category) {
            if (isset($category['person_name']) && !empty($category['person_name'])) {
                $webmention_text .= '<a href="' . $category['url'] . '"></a>' ;
            }
        }

        $webmention_text .= html_entity_decode($old_text .' ' .$post['content'] . $post['syndication_extra']);
        // send webmention
        $client = new IndieWeb\MentionClient($post['permalink'], $webmention_text, false, $post['shortlink']);

        $client->debug(false);
        //TODO
        $this->load->model('webmention/vouch');
        $searcher = $this->model_webmention_vouch;
        $sent = $client->sendSupportedMentions($searcher);
        //
        //$sent = $client->sendSupportedMentions();
        $urls = $client->getReturnedUrls();
        foreach ($urls as $syn_url) {
            $this->model_blog_post->addSyndication($post_id, $syn_url);
        }

        $this->cache->delete('post.' . $post_id);
    }

    public function processcontexts()
    {
        $result = $this->db->query("SELECT * FROM " . DATABASE . ".posts 
             JOIN " . DATABASe . ".post_reply_to ON posts.id = post_reply_to.post_id
             WHERE context_parsed=0 
             LIMIT 1" );

        $post = $result->row;

        while (!empty($post)) {
            //immediately update this to say that it is parsed.. this way we don't end up trying to run it multiple times on the same post
            $this->db->query("UPDATE " . DATABASE . ".posts SET context_parsed = 1 WHERE id = " . (int)$post_id);

            $source_url = trim($post['in-reply-to']); //todo want to support multiples

            $post_id = $post['id'];
            $context_id = $this->getContextId($source_url);

            if ($context_id) {
                $this->db->query("INSERT INTO " . DATABASE . ".context_post SET 
                    post_id = " . (int)$post_id . ",
                    context_id = " . (int)$context_id);
            }


            $result = $this->db->query("SELECT * FROM " . DATABASE . ".posts WHERE NOT `in-reply-to` is NULL AND context_parsed=0 LIMIT 1");
            $post = $result->row;

        } //end while($post) loop
        $this->cache->delete('context');
    }

    private function getContextId($source_url)
    {

        //todo check if $source_url is a syndicated copy of my own posts
        $result = $this->db->query("SELECT post_id FROM " . DATABASE . ".post_syndication WHERE url = '" . $source_url . "' LIMIT 1");
        if ($result->row) {
            $this->load->model('blog/post');
            $post = $this->model_blog_post->getPost($result->row['post_id']);
            $source_url = $post['permalink'];

        }

        //todo check if $source_url is a syndicated copy of comments on my site

        //download the page content
        $c = curl_init();
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_URL, $source_url);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($c, CURLOPT_MAXREDIRS, 20);
        $real_source_url = curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
        $page_content = curl_exec($c);
        curl_close($c);
        unset($c);

        if ($page_content === false) {
            return null;
        }

        //attempt to part as mf2
        $mf2_parsed = Mf2\parse($page_content, $real_source_url);
        if (empty($mf2_parsed['items'])) {
            //if not items found lets try with twitter shim
            $mf2_parsed = Mf2\Shim\parseTwitter($page_content, $real_source_url);
        }
        if (empty($mf2_parsed['items'])) {
            //we give up
            return null;
        }

        //try to find the correct item
        foreach ($mf2_parsed['items'] as $item) {
            $source_data = IndieWeb\comments\parse($item);
            if (!empty($source_data['url']) && $source_data['url'] == $real_source_url) {
                break;
            }
        }

        //we never found an item with any properties
        if (empty($source_data['url'])) {
            return null;
        }

        $real_url = $source_data['url'];

        $query = $this->db->query("SELECT * FROM " . DATABASE . ".contexts WHERE url='" . $this->db->escape($real_url) . "' LIMIT 1");

        if (!empty($query->row)) {
            return $query->row['id'];

        }

        // look up url in context_syndications and if there use that id

        $query = $this->db->query(
            "SELECT * " .
            " FROM " . DATABASE . ".context_syndication " .
            " WHERE url='" . $this->db->escape($real_url) . "' " .
            " LIMIT 1"
        );

        if (!empty($query->row)) {
            return $query->row['context_id'];
        }

        $published = $source_data['published'];
        $body = $source_data['text'];
        $name = $source_data['name'];


        // do our best to conver to local time
        date_default_timezone_set(LOCALTIMEZONE);
        $date = new DateTime($published);
        $now = new DateTime;
        $tz = $now->getTimezone();
        $date->setTimezone($tz);
        $published = $date->format('Y-m-d H:i:s') . "\n";

        $this->load->model('blog/person');
        $person_id = $this->model_blog_person->storePerson($source_data['author']);

        $this->db->query("INSERT INTO " . DATABASE . ".contexts SET 
            person_id = ".(int)$person_id . "
            name = '" . $this->db->escape($name) . "',
            url = '" . $this->db->escape($real_url) . "',
            content = '" . $this->db->escape($body) . "',
            published ='" . $published . "'");

        $context_id = $this->db->getLastId();

        $syndication_sites = $this->cache->get('syndication.sites');
        if (!$syndication_sites) {
            $syn_site_query = $this->db->query("SELECT * FROM " . DATABASE . ".syndication_sites");
            $syndication_sites = $syn_site_query->rows;
            $this->cache->set('syndication.sites', $syndication_sites);
        }

        if (isset($sourch_data['syndications'])) {
            foreach ($sourch_data['syndications'] as $url) {
                // figure out what syndicaiton_site_id to use
                foreach ($syndication_sites as $possible_site) {
                    if (strpos($url, $possible_site['url_match']) === 0) {
                        $syn_site_id = $possible_site['id'];
                    }
                }


                $this->db->query("INSERT INTO " . DATABASE . ".context_syndication 
                    SET url = '" . $this->db->escape($url) . "',
                        " . (isset($syn_site_id) ? "id = " . (int)$syn_site_id . ", " : "" ) . "
                        context_id = " . (int)$context_id);

                //remove any syndicated copies we have already parsed
                $query = $this->db->query(
                    "SELECT * " .
                    " FROM " . DATABASE . ".contexts " .
                    " WHERE url='" . $this->db->escape($url) . "' " .
                    " LIMIT 1"
                );
                if (!empty($query->row)) {
                    $this->db->query(
                        "DELETE FROM " . DATABASE . ".contexts " .
                        " WHERE url='" . $this->db->escape($url) . "' " .
                        " LIMIT 1"
                    );
                    $this->db->query(
                        "UPDATE " . DATABASE . ".context_context " .
                        " SET parent_id = " . (int)$context_id . " " .
                        " WHERE parent_id=" . (int)$query->row['context_id']
                    );
                }
            }
        }

        foreach ($mf2_parsed['items'] as $item) {
            if (isset($item['properties']) && isset($item['properties']['in-reply-to']) && !empty($item['properties']['in-reply-to'])) {
                foreach ($item['properties']['in-reply-to'] as $citation) {
                    if (isset($citation['properties'])) {
                        foreach ($citation['properties']['url'] as $reply_to_url) {
                            $ctx_id = $this->getContextId($reply_to_url);
                            if ($ctx_id) {
                                $this->db->query("INSERT INTO " . DATABASE . ".context_context SET 
                                child_id = " . (int)$context_id . ",
                                parent_id = " . (int)$ctx_id);
                            }

                        }
                    } else {
                        $reply_to_url = $citation;

                        $ctx_id = $this->getContextId($reply_to_url);
                        if ($ctx_id) {
                            $this->db->query("INSERT INTO " . DATABASE . ".context_context SET 
                            child_id = " . (int)$context_id . ",
                            parent_id = " . (int)$ctx_id);
                        }
                    }
                }
                return $context_id;
            }
        }
        return $context_id;

    }

}
