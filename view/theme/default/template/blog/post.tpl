<?php echo $header; ?>
<?php date_default_timezone_set(LOCALTIMEZONE); ?> 
<div>
  <div class="context_history">
  <?php foreach($post['context'] as $ctx){ ?>
        <div class="comment h-cite entry-meta" >
            <div class="comment_header">    
                <span class="minicard h-card p-author">
                    <img class="comment_author logo u-photo" src="<?php echo $ctx['author']['image']?>" alt="<?php echo $ctx['author']['name']?>" width="48" />
                    <a class="p-name u-url" href="<?php echo $ctx['author']['url']?>"><?php echo $ctx['author']['name']?></a>
                </span>
                <a href="<?php echo $ctx['source_url']?>" class="u-url permalink"><time class="date dt-published" datetime="<?php echo $ctx['published']?>"><?php echo date("F j, Y g:i A", strtotime($ctx['published']))?></time></a>
            </div>
                                                           
            <div class="h-cite entry-meta comment_body">
                <div class="quote-text"><div class="e-content p-name"><?php echo $ctx['content']?></div></div>
            </div>
        </div>
    <?php } ?>
    </div>

          <article id="post-<?php echo $post['id']?>" class="<?php echo $post['type']?> type-<?php echo $post['type']?> <?php echo ($post['draft'] == 1 ? 'draft':'') ?> <?php echo ($post['deleted'] == 1 ? 'deleted':'') ?>">

    <header class="entry-meta comment_header">
        <div class="entry-meta">      
        <span class="author p-author  hcard h-card">
            <img alt='' src='<?php echo $post['author']['image']?>' class='u-photo ' height='40' width='40' /> 
            <span class="p-name"><a class="url u-url" href="<?php echo $post['author']['link']?>" title="<?php echo $post['author']['display_name']?>" rel="author">
                <?php echo $post['author']['display_name']?>
            </a></span>
        </span>
        <a href="<?php echo $post['permalink']?>" title="<?php echo date("g:i A", strtotime($post['published']))?>" rel="bookmark" class="permalink u-url"> <time class="entry-date updated published dt-updated dt-published" datetime="<?php echo $post['published']?>" ><?php echo date("F j, Y g:i A", strtotime($post['published']))?></time></a>

        <a href="<?php echo $post['shortlink']?>" title="Shortlink" rel="shortlink" class="shortlink u-shortlink u-url">Shortlink</a>

        <span class='in_reply_url'>
        <?php if(!empty($post['in-reply-to'])){ ?>
       In Reply To <a class="u-in-reply-to" rel="in-reply-to" href="<?php echo $post['in-reply-to']?>"><?php echo $post['in-reply-to']?></a>
       <?php } ?>
       </span>
        </div><!-- .entry-meta -->
    </header>
  <div class='articlebody'>

        <?php echo $postbody?>

  </div>
  
  <footer class="entry-meta">

  <?php if(!empty($post['syndications'])){ ?>
    <div id="syndications">
    <?php foreach($post['syndications'] as $elsewhere){ ?>

      <?php if(isset($elsewhere['image'])){ ?>
      <a class="u-syndication" href="<?php echo $elsewhere['url']?>" ><img src="<?php echo $elsewhere['image']?>" title="<?php echo $elsewhere['name']?>" /></a>
      <?php } else { ?>
      <a class="u-syndication" href="<?php echo $elsewhere['url']?>" ><i class="fa fa-link"></i></a>
      <?php } ?>
      
    <?php } //end foreach ?>
    </div>
  <?php } ?>
    <div class="admin-controls">
      <?php foreach($post['actions'] as $actiontype => $action){ ?>
      <indie-action do="<?php echo $actiontype?>" with="<?php echo $post['permalink']?>">
      <a href="<?php echo $action['link'] ?>" title="<?php echo $action['title']?>"><?php echo $action['icon']?></a>
      </indie-action>
      <?php } ?>
    </div>

    <?php if(!empty($post['reacjis']) ) { ?>
    <span id="general-reacjis">
        <?php foreach($post['reacjis'] as $reacji => $rdata){ ?>
        <span class="reacji-container">
                <span class="reacji"><?php echo $reacji?></span>
                <span class="reacji-count"><?php echo count($rdata)?></span>
                <span class="reacji-sources">
    
                <?php foreach($rdata as $comment){ ?>
                    <div class="h-cite u-comment">
                        <time class="date dt-published" style="display:none" datetime="<?php echo $comment['published']?>"><?php echo date("Y-m-d", strtotime($comment['published']))?></time></a>
                        <span class="h-card u-author">
                            <a class="u-url" href="<?php echo (isset($comment['author']['url']) ? $comment['author']['url']: $comment['source_url'])?>" rel="nofollow" title="View Profile">
                                <img class='comment_author u-photo' src="<?php echo (isset($comment['author']['image']) ? $comment['author']['image']: '/image/person.png') ?>" />
                                <span class="p-name" style="display:none"><?php echo (isset($comment['author']['name']) ? $comment['author']['name']: 'someone') ?></span>
                            </a>
                        </span>
                        <a href="<?php echo $comment['source_url']?>" class="u-url permalink" title="<?php echo date("Y-m-d", strtotime($comment['published']))?>"><?php echo (isset($comment['author']['name']) ? $comment['author']['name']: 'someone') ?></a>

                        <div class='p-content p-name' style="display:none">
                            <?php echo $comment['content']?>
                        </div>
                    </div>
                <?php  } ?>

                </span>
        </span>
        <?php } ?>

    <div style="clear:both"></div>
        </span>
    <?php } ?>



  <?php if($post['categories']){ ?>
      <?php foreach($post['categories'] as $category) { ?>
          <?php if(isset($category['person_name'])){ ?>
              <span class="category-link"><a class="u-category h-card" href="<?php echo $category['url']?>" title="<?php echo $category['url']?>"><?php echo $category['person_name']?></a></span>
          <?php } else { ?>
              <span class="category-link"><a class="u-category" href="<?php echo $category['permalink']?>" title="<?php echo $category['name']?>"><?php echo $category['name']?></a></span>
          <?php } ?>
  
      <?php } // end for post_categories as category ?>
  <?php } // end if post_categories ?>


    <?php if($post['like_count'] > 0) { ?>
    <br>
    <span id="general-likes"><a class="like"><h3 class="widget-title"><?php echo $post['like_count'] . ($post['like_count'] > 1 ? ' People' : ' Person')?> Liked This Post</h3></a>
        <?php foreach($post['likes'] as $like){?>
                <span class="likewrapper h-cite p-like">
                <a class="u-url" href="<?php echo $like['source_url']?>" rel="nofollow">
                    <img class='like_author' src="<?php echo (isset($like['author']['image']) ? $like['author']['image']: '/image/person.png') ?>"
                        title="<?php echo (isset($like['author']['name']) ? $like['author']['name']: 'Author Image') ?>" /></a>
                </span>
        <?php } ?>
    <div style="clear:both"></div>
	</span>
    <?php } ?>

    <?php if($post['repost_count'] > 0) { ?>
    <br>
    <span id="general-reposts"><a class="repost"><h3 class="widget-title"><?php echo $post['repost_count'] . ($post['repost_count'] > 1 ? ' People' : ' Person')?> Reposted This Post</h3></a>
        <?php foreach($post['reposts'] as $repost){?>
                <span class="repostwrapper h-cite p-repost">
                <a class="u-url" href="<?php echo $repost['source_url']?>" rel="nofollow">
                    <img class='repost_author' src="<?php echo (isset($repost['author']['image']) ? $repost['author']['image']: '/image/person.png') ?>"
                        title="<?php echo (isset($repost['author']['name']) ? $repost['author']['name']: 'Author Image') ?>" /></a>
                </span>
        <?php } ?>
    <div style="clear:both"></div>
	</span>
    <?php } ?>

   <?php if(isset($post['created_by'])){ 
        $client_id = strtolower($post['created_by']);
        if(preg_match('/https?:\/\/.+\..+/', $client_id)){ ?>
            <div class="client_line">Created by <a class="u-x-client-id" href="<?php echo $post['created_by']?>"><?php echo $post['created_by']?></a></div>
        <?php } else { ?>
            <div class="client_line">Created by <span class="p-x-client-id"><?php echo $post['created_by']?></span></div>
        <?php } ?>
    <?php } ?>
  </footer><!-- #entry-meta --></article><!-- #post-<?php echo $post['id']?> -->

    <?php if($post['comment_count'] > 0) { ?>
    <div class="comments">
        <?php foreach($post['comments'] as $comment) { ?>
            <div class="comment u-comment h-cite">
                <div class='comment_header'>
                    <span class="minicard h-card u-author">
                        <img class='comment_author u-photo' src="<?php echo (isset($comment['author']['image']) ? $comment['author']['image']: '/image/person.png') ?>" />
                        <a class="p-name u-url" href="<?php echo (isset($comment['author']['url']) ? $comment['author']['url']: $comment['source_url'])?>" rel="nofollow" title="<?php echo (isset($comment['author']['name']) ? $comment['author']['name']: 'View Author') ?>" ><?php echo (isset($comment['author']['name']) ? $comment['author']['name']: 'A Reader') ?></a>
                    </span>

                    <a href="<?php echo $comment['source_url']?>" class="u-url permalink"><time class="date dt-published" datetime="<?php echo $comment['published']?>"><?php echo date("F j, Y g:i A", strtotime($comment['published']))?></time></a>
                    <?php if($comment['vouch_url']) { ?>
                        <a href="<?php echo $comment['vouch_url']?>" class="vouch">Vouched</a>
                    <?php } ?>
                   <span class="other-controls">
                      <?php foreach($comment['actions'] as $actiontype => $action){ ?>
                      <indie-action do="<?php echo $actiontype?>" with="<?php echo $comment['permalink']?>">
                      <a href="<?php echo $action['link'] ?>" title="<?php echo $action['title']?>"><?php echo $action['icon']?></a>
                      </indie-action>
                      <?php } ?>
                  </span>
                </div>
                <div class='comment_body p-content p-name'>
                    <?php echo $comment['content']?>
                </div>
                <?php foreach($comment['comments'] as $subcomment) { ?>
                    <div class="subcomment u-comment h-cite">
                    
                        <div class='comment_header'>
                            <span class="minicard h-card u-author">
                                <img class='comment_author' src="<?php echo (isset($subcomment['author']['image']) ? $subcomment['author']['image']: '/image/person.png') ?>" />
                                <a class="p-name u-url" href="<?php echo (isset($subcomment['author']['url']) ? $subcomment['author']['url']: $subcomment['source_url'])?>" rel="nofollow" title="<?php echo (isset($subcomment['author']['name']) ? $subcomment['author']['name']: 'View Author') ?>" ><?php echo (isset($subcomment['author']['name']) ? $subcomment['author']['name']: 'A Reader') ?></a>
                            </span>

                            <a href="<?php echo $subcomment['source_url']?>" class="u-url permalink"><time class="date dt-published" datetime="<?php echo $subcomment['published']?>"><?php echo date("F j, Y g:i A", strtotime($subcomment['published']))?></time></a>
                        </div>
                        <div class='comment_body p-content p-name'>
                            <?php echo $subcomment['content']?>
                        </div>
                    </div>

                <?php } // end foreach subcomment ?>

            </div>
        <?php } ?>
	</div>
    <?php } ?>
</div>

<?php echo $footer; ?>
