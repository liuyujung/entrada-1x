<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 *
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * A model to handle quizzes
 *
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Ryan Warner <rw65@queensu.ca>
 * @copyright Copyright 2014 Queen's University. All Rights Reserved.
 */

class Models_Quiz extends Models_Base {
    
    protected $quiz_id, $quiz_title, $quiz_description, $quiz_active = 1, $updated_date, $updated_by, $created_by;
    
    protected $table_name = "quizzes";
    protected $default_sort_column = "quiz_title";

    public function __construct($arr = NULL) {
        parent::__construct($arr);
    }
    
    public static function fetchRowByID($quiz_id) {
        $self = new self();
        return $self->fetchRow(array(
                array("key" => "quiz_id", "value" => $quiz_id, "method" => "=", "mode" => "AND")
            )
        );
    }

    public static function fetchAllRecords($quiz_active = 1) {
        $self = new self();

        $constraints = array(
            array(
                "mode"      => "AND",
                "key"       => "quiz_active",
                "value"     => $quiz_active,
                "method"    => "="
            )
        );

        $objs = $self->fetchAll($constraints, "=", "AND");

        $output = array();

        if (!empty($objs)) {
            foreach ($objs as $o) {
                $output[] = $o;
            }
        }

        return $output;
    }
    
    public static function fetchAllRecordsByProxyID($proxy_id, $quiz_active = 1) {
        global $db;
        
        $output = false;
        
        $query = "SELECT a.*
					FROM `quizzes` AS a
					JOIN `quiz_contacts` AS b
					ON a.`quiz_id` = b.`quiz_id`
					WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
					AND a.`quiz_active` = 1
					GROUP BY a.`quiz_id`";
        $results = $db->GetAll($query);
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        
        return $output;
        
    }
    
    public static function fetchAllRecordsByProxyIDQuizID($proxy_id, $quiz_id, $quiz_active = 1) {
        global $db;
        
        $output = false;
        
        $query = "SELECT a.*
					FROM `quizzes` AS a
					JOIN `quiz_contacts` AS b
					ON a.`quiz_id` = b.`quiz_id`
					WHERE b.`proxy_id` = ".$db->qstr($proxy_id)."
                    OR a.`quiz_id` = ".$db->qstr($quiz_id)."
					AND a.`quiz_active` = 1
					GROUP BY a.`quiz_id`";
        $results = $db->GetAll($query);
        if ($results) {
            $output = array();
            foreach ($results as $result) {
                $output[] = new self($result);
            }
        }
        
        return $output;
        
    }
    
    public function getQuizID() {
        return $this->quiz_id;
    }

    public function getQuizTitle() {
        return $this->quiz_title;
    }

    public function getQuizDescription() {
        return $this->quiz_description;
    }

    public function getQuizActive() {
        return $this->quiz_active;
    }

    public function getUpdatedDate() {
        return $this->updated_date;
    }

    public function getUpdatedBy() {
        return $this->updated_by;
    }

    public function getCreatedBy() {
        return $this->created_by;
    }
    
    public function getQuizAuthor() {
        $author = User::fetchRowByID($this->created_by);
        if ($author) {
            return $author;
        } else {
            return false;
        }
    }
    
    public function getQuizQuestions() {
        return Models_Quiz_Question::fetchAllRecords($this->quiz_id);
    }
    
    public function insert() {
        global $db;
        
        if ($db->AutoExecute($this->table_name, $this->toArray(), "INSERT")) {
            $this->quiz_id = $db->Insert_ID();
            return $this;
        } else {
            return false;
        }
    }
    
    public function update() {
        global $db;
        
        if ($db->AutoExecute($this->table_name, $this->toArray(), "UPDATE", "`quiz_id` = ".$db->qstr($this->quiz_id))) {
            return $this;
        } else {
            return false;
        }
    }
    
    public function delete() {
        global $db;
        
        $query = "DELETE FROM `".$this->table_name."` WHERE `quiz_id` = ?";
        if ($db->Execute($query, $this->quiz_id)) {
            return true;
        } else {
            return false;
        }
    }
    
}

?>
