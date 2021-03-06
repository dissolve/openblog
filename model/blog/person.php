<?php
class ModelBlogPerson extends Model {


    /**
     *  Save a person or recall an existing one
     *  @arg Data array the array of data describing a person assumed to contain
     *    name -the person's name
     *    url - the url of the person
     *    image - url of the image of the person to display
     *  @return int the person_id of the (possibly) newly created person
     *      returns null if error
     */
    public function storePerson($data)
    {

        $person_id = $this->getPersonByUrl($data['url']);

        if ($person_id) {
            return $person_id;
        }

        if (empty($data['image']) && !empty($data['photo'])) {
            $data['image'] = $data['photo'];
        }

        if ( !isset($data['url']) && !isset($data['name']) && !isset($data['image']) ) {
            return null;
        }

        $data['url'] = $this->standardizeUrl($data['url']);

        $this->db->query(
            "INSERT INTO " . DATABASE . ".people " .
            " SET " .
            " `name`='" . $this->db->escape($data['name']) . "', " .
            " `image`='" . $this->db->escape($data['image']) . "' "
        );

        $id = $this->db->getLastId();

        $this->db->query(
            "INSERT INTO " . DATABASE . ".person_url " .
            " SET " .
            " `url`='" . $this->db->escape($data['url']) . "', " .
            " `person_id`=" . (int)$id
        );

        $this->cache->delete('people');

        return $id;

    }

    public function getPersonByUrl($url)
    {
        $url = $this->standardizeUrl($url);

        $query = $this->db->query(
            "SELECT person_id " .
            " FROM " . DATABASE . ".person_url " .
            " WHERE `url` = '" . $this->db->escape($url) . "' ;"
        );

        if ($query->row) {
            return $query->row['person_id'];
        }

        return null;
    }

    public function getPerson($person_id)
    {
        $person = $this->cache->get('person.' . $person_id);
        if (!$person) {
            $query = $this->db->query(
                "SELECT * " .
                " FROM " . DATABASE . ".people " .
                " WHERE id = " . (int)$person_id
            );
            $person = $query->row;

            $query = $this->db->query(
                "SELECT * " .
                " FROM " . DATABASE . ".person_url " .
                " WHERE person_id = " . (int)$person_id .
                " AND primary = 1 "
            );
            $person['url'] = $query->row['url'];

            $query = $this->db->query(
                "SELECT * " .
                " FROM " . DATABASE . ".person_url " .
                " WHERE person_id = " . (int)$person_id .
                " AND primary = 0 " .
                " ORDER BY url"
            );
            $person['alternates'] = $query->rows;

            $this->cache->set('person.' . $person_id, $person);
        }
        return $person;
    }

    public function getPeople($limit = null, $skip = null)
    {
        $people = $this->cache->get('people.' . $limit . '.' . $skip);
        if (!$people) {
            $query = $this->db->query(
                "SELECT * " .
                " FROM " . DATABASE . ".people " .
                " ORDER BY name" .
                ($limit
                ? " LIMIT " . $limit .
                    ( $skip
                    ? " OFFSET " . $skip
                    : "")
                : "")
            );
            $people = $query->rows;
            foreach ($people as &$person) {
                $query = $this->db->query(
                    "SELECT * " .
                    " FROM " . DATABASE . ".person_url " .
                    " WHERE person_id = " . (int)$person['id'] .
                    " AND primary = 1 "
                );
                $person['url'] = $query->row['url'];
                $query = $this->db->query(
                    "SELECT * " .
                    " FROM " . DATABASE . ".person_url " .
                    " WHERE person_id = " . (int)$person['id'] .
                    " AND primary = 0 " .
                    " ORDER BY url"
                );
                $person['alternates'] = $query->rows;
            }
            $this->cache->set('people' . $limit . '.' . $skip, $people);
        }
        return $people;
    }

    public function joinPeople($main_person_id, $alternate_person_id)
    {
        //TODO: make this only change the person_id on all URLs 

        $alt_person = $this->getPerson($alternate_person_id);
        $this->addAlternateUrl($main_person_id, $alt_person['url']);

        foreach ($alt_person['alternates'] as $alt) {
            $this->addAlternateUrl($main_person_id, $alt['url']);
        }

        // update people in interactions and contexts

        $this->db->query(
            "UPDATE " . DATABASE . ".interactions " .
            " SET person_id = '" . (int)$main_person_id . "' " .
            " WHERE person_id = " . (int)$alternate_person_id
        );

        $this->db->query(
            "UPDATE " . DATABASE . ".contexts " .
            " SET person_id = '" . (int)$main_person_id . "' " .
            " WHERE person_id = " . (int)$alternate_person_id
        );


        $this->deletePerson($alternate_person_id);

        $this->cache->delete('person.' . $main_person_id);
        $this->cache->delete('person.' . $alternate_person_id);

        $this->cache->delete('people');
    }

    public function addAlternateUrl($person_id, $alternate_url)
    {
        //todo, prevent adding an alternate that is the same as the master?
        $query = $this->db->query(
            "SELECT * " .
            " FROM " . DATABASE . ".person_url " .
            " WHERE person_id = " . (int)$person_id . ", " .
            " AND url = '" . $this->db->escape($alternate_url) . "' "
        );

        if (empty($query->row)) {
            $this->db->query(
                "INSERT INTO " . DATABASE . ".person_url " .
                " SET person_id = " . (int)$person_id . ", " .
                " primary = 0, " .
                " url = '" . $this->db->escape($alternate_url) . "' "
            );
        }
        $this->cache->delete('person.' . $person_id);
    }

    public function removeAlternateUrl($person_id, $alternate_url)
    {
        $this->db->query(
            "DELETE FROM " . DATABASE . ".person_url " .
            " WHERE person_id = " . (int)$person_id . ", " .
            " AND url = '" . $this->db->escape($alternate_url) . "' " .
            " LIMIT 1"
        );
        $this->cache->delete('person.' . $person_id);

    }

    private function deletePerson($person_id)
    {
        //TODO check if this person is associated to anything first before delete
        $this->db->query(
            "DELETE FROM " . DATABASE . ".person_url " .
            " WHERE person_id = " . (int)$person_id
        );
        $this->db->query(
            "DELETE FROM " . DATABASE . ".people " .
            " WHERE id = " . (int)$person_id
        );
        return true;
    }

    public function setPrimaryUrl($person_id, $url)
    {
        $url = $this->standardizeUrl($url);
        $this->db->query(
            "UPDATE " . DATABASE . ".person_url " .
            " SET primary = 0 " . 
            " WHERE person_id = " . (int)$person_id
        );

        $query = $this->db->query(
            "SELECT * FROM " . DATABASE . ".person_url " .
            " WHERE person_id = " . (int)$person_id .
            " AND url = '" . $this->db->escape($url) . "' "
        );
        if($query->num_rows > 0){
            $this->db->query(
                "UPDATE " . DATABASE . ".person_url " .
                " SET primary = 1 " . 
                " WHERE id = " . (int)$query->row['id']
            );
        } else {
            $this->db->query(
                "INSERT INTO " . DATABASE . ".person_url " .
                " SET person_id = " . (int)$person_id . ", " .
                " primary = 1, " .
                " url = '" . $this->db->escape($url) . "' "
            );
        }

        $this->cache->delete('person.' . $person_id);
    }

    private function standardizeUrl($url)
    {
        $url = trim($url);
        $url = rtrim($url, '/');
        return  $url;
    }

}
