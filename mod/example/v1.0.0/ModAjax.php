<?php
require_once("core2/inc/ajax.func.php");
	
class ModAjax extends ajaxFunc {
	
	public function __construct(xajaxResponse $res) {
			parent::__construct($res);
	}

    /*
     * Сохранение события
     */
    public function axSaveEvents($data) {
        //print_r($data);
        $fields = array(
            'type' => 'req',
            'title' => 'req',
            'body' => 'req',
        );
        if ($this->ajaxValidate($data, $fields)) {
            return $this->response;
        }


        $errors = array();
        try {
            if ($data['control']['type'] == 'birthday') {
                $event = $this->db->fetchOne("SELECT id
                        FROM mod_staff_events
                        WHERE type = 'birthday'
                        AND id <> ?", $data['params']['edit']);
                if ($event) {
                    throw new Exception("Событие День рождения уже есть в базе");
                }
            }
            $this->saveData($data);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        if (count($errors) == 0) {
            $this->done($data);
        } else {
            $msgerror = implode(", ", $errors);
            $this->error[] = $msgerror;
            $this->displayError($data);
        }
        return $this->response;
    }

    /*
     * Сохранение пользователя
     */
    public function axSaveStaff($data) {
        //print_r($data);
        $fields = array(
            'name' => 'req',
            'email' => 'req',
            'tel' => 'req',
        );
        if ($this->ajaxValidate($data, $fields)) {
            return $this->response;
        }

        $errors = array();
        try {

            if ($data['xxx_day'] && $data['xxx_month']) {
                $dt = new DateTime("2015-" . $data['xxx_month'] . "-" . $data['xxx_day']);
                $data['control']['birthday'] = $dt->format("Y-m-d");
            } else {
                $data['control']['birthday'] = null;
            }
            $this->saveData($data);
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }

        if (count($errors) == 0) {
            $this->done($data);
        } else {
            $msgerror = implode(", ", $errors);
            $this->error[] = $msgerror;
            $this->displayError($data);
        }
        return $this->response;
    }
}
