<?php
require_once(DOC_ROOT . "core2/inc/classes/class.list.php");
require_once(DOC_ROOT . "core2/inc/classes/class.edit.php");
require_once(DOC_ROOT . "core2/inc/classes/class.tab.php");
require_once(DOC_ROOT . "core2/inc/classes/Templater2.php");
require_once(DOC_ROOT . "core2/inc/classes/Alert.php");
/**
 * Class ModExampleController
 */
class ModExampleController extends Common {

    private $month = array(
        '01' => 'января',
        '02' => 'февраля',
        '03' => 'марта',
        '04' => 'апреля',
        '05' => 'мая',
        '06' => 'июня',
        '07' => 'июля',
        '08' => 'августа',
        '09' => 'сентября',
        '10' => 'октября',
        '11' => 'ноября',
        '12' => 'декабря'
    );

    public function action_index() {
        if (!empty($_GET['get'])) {
            if ($_GET['get'] == 'ldap') {
                require_once("Zend/Ldap.php");

                $options = $this->config->ldap->belhard;
                $ldap = new Zend_Ldap($options);
                $ldap->bind();
                //$carl = $ldap->getEntry('cn=*,ou=People');
                //$carl = $ldap->getEntry('OU=Users,OU=BelHard,DC=belhard,DC=com');
                $items = $ldap->searchEntries('(objectClass=user)', 'OU=Users,OU=BelHard,DC=belhard,DC=com', 'cn=*');

                $users = array();
                foreach ($items as $item) {
                    $data = array('name' => $item['displayname'], 'company' => $item['company'], 'mail' => $item['mail'], 'objectclass' => $item['objectclass']);
                    $data = array('name' => current($item['displayname']), 'email' => current($item['mail']));
                    //$users[] = $user;
                    if ($item['mail']) {
                        $user_id = $this->db->fetchOne("SELECT id FROM mod_staff WHERE LOWER(email)=?", strtolower($item['mail']));
                        if ($user_id) {
                            $this->db->update('mod_staff', $data, "id={$user_id}");
                        } else {
                            $data['is_active_sw'] = 'Y';
                            $this->db->insert('mod_staff', $data);
                        }
                    }
                }
                //echo "<pre>";print_r($users);echo "</pre>";die;
                exit;
            }
        }
    }

    public function action_staff() {
        //экспорт новых пользователей и даты рождения старых пользователей из файла
        if (isset($_GET['act']) && $_GET['act'] == 'import_from_csv') {
            $data2 = array();
            if (($handle = fopen($this->getModuleLocation('staff') . "/import.csv", "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 10000, "\n")) !== FALSE) {
                    $num = count($data);
                    for ($c=0; $c < $num; $c++) {
                        $line = explode(';', $data[$c]);
                        $data2[] = $line;
                    }
                }
                fclose($handle);
            }

            foreach ($data2 as $user) {
                if ($user[8]) {
                    $user_data = $this->db->fetchRow("SELECT id
                                                 FROM mod_staff
                                                 WHERE LOWER(email) = ?", strtolower($user[8]));
                    if ($user_data['id']) {
                        $dt = new DateTime("2015-{$user[1]}-{$user[0]}");
                        $where = $this->db->quoteInto("id = ?", $user_data['id']);
                        $this->db->update("mod_staff", array(
                            'birthday' => $dt->format("Y-m-d")
                        ), $where);
                    } else {
                        $dt = new DateTime("2015-{$user[1]}-{$user[0]}");
                        $name = "{$user[3]} {$user[4]} {$user[5]}";
                        $this->db->insert('mod_staff', array(
                            'name' => $name,
                            'birthday' => $dt->format("Y-m-d"),
                            'email' => $user[8]
                        ));
                    }
                }
            }
            echo "ok";
            exit;
        }
        $app = "index.php?module={$this->module}&action={$_GET['action']}";
        $tab = new tabs($this->resId);

        $title = "Кадры";
        $tab->beginContainer($title);
        $this->printJs($this->getModuleSrc('staff') . "/staff.js");
        if ($tab->activeTab == 1) {
            if(isset($_GET['edit']) && $_GET['edit']!="") {
                $edit = new editTable($this->resId);
                $id = (int)$_GET['edit'];
                $edit->SQL  = "SELECT id,
                                    name,
                                    birthday,
                                    email,
                                    tel,
                                    is_active_sw
                                FROM mod_staff
                                WHERE id = $id";
                $edit->addControl("ФИО:", "TEXT", "maxlength=\"255\" size=\"30\" ", "", "", true);
                $html_day = "";
                $html_month = "";
                if ($id) {
                    $data = $this->db->fetchRow("SELECT birthday
                                                FROM mod_staff
                                                WHERE id = ?", $id);
                    if ($data['birthday']) {
                        $dt = new DateTime($data['birthday']);
                        $html_day = $dt->format("d");
                        $html_month = $dt->format("m");
                    }
                }
                $html = "День: <input type='text' class='input' size='2' name='xxx_day' value='$html_day'>
                        Месяц: <input type='text' class='input' size='2' name='xxx_month' value='$html_month'>";
                $edit->addControl("День рождения:", "CUSTOM", $html, "", "");
                $edit->addControl("Email:", "TEXT", "maxlength=\"255\" size=\"30\" ", "", "", true);
                $edit->addControl("Телефон:", "TEXT", "maxlength=\"20\" size=\"30\" ", "", "", true);

                $edit->back = $app;
                $edit->save("xajax_SaveStaff(xajax.getFormValues(this.id))");
                $edit->addButton("Отменить", "load('$app')");
                $edit->showTable();
            }

            echo "<div id=\"dd\"></div>";

            $list = new listTable($this->resId);
            $list->addButtonCustom('<input class="button" type="button" value="Синхронизировать" onclick="staff.sync();">');
            $list->addSearch("ФИО", "name", "TEXT");
            $list->addSearch("День рождения", "birthday", "DATE");
            $list->addSearch("Email", "email", "TEXT");
            $list->addSearch("Телефон", "tel", "TEXT");
            $list->SQL = "SELECT id,
                        name,
                        birthday,
                        email,
                        tel,
                        is_active_sw
						FROM mod_staff
						WHERE 1=1 ADD_SEARCH";

            $list->addColumn("ФИО", "", "TEXT");
            $list->addColumn("День рождения", "", "TEXT");
            $list->addColumn("Email", "", "TEXT");
            $list->addColumn("Телефон", "", "TEXT");
            $list->addColumn("", "1%", "STATUS_INLINE", "mod_staff.is_active_sw");
            $list->addURL 			= $app . "&edit=0";
            $list->editURL 			= $app . "&edit=TCOL_00";
            $list->deleteKey		= "mod_staff.id";
            $list->getData();
            foreach ($list->data as $k => $v) {
                if ($list->data[$k][2]) {
                    $birthday = new DateTime($list->data[$k][2]);
                    $list->data[$k][2] = $birthday->format("d") . " " . $this->month[$birthday->format("m")];
                } else {
                    $list->data[$k][2] = "";
                }


            }
            $list->showTable();
        }

        $tab->endContainer();

    }

    public function action_events() {
        //$this->checkEvents();
        $app = "index.php?module={$this->module}&action={$_GET['action']}";
        $tab = new tabs($this->resId);
        $type = $this->moduleConfig->event_type->toArray();
        $title = "События";
        $tab->beginContainer($title);
        //$this->printJs($this->getModuleSrc('events') . "/events.js");
        if ($tab->activeTab == 1) {
            if(isset($_GET['edit']) && $_GET['edit']!="") {
                $edit = new editTable($this->resId);
                $id = (int)$_GET['edit'];
                $edit->SQL  = "SELECT id,
                        type,
                        title,
                        body,
                        'file'
						FROM mod_staff_events
						WHERE id = $id";
                $edit->addControl("Тип:", "LIST", "", "", "", true);
                $edit->selectSQL[] = $type;
                $edit->addControl("Тема письма:", "TEXT", "maxlength=\"255\" size=\"60\" ", "", "", true);
                $edit->addControl("Текст письма:", "FCK_BASIC", " cols=\"57\" rows=\"10\"", "", "", true);
                $edit->addControl("Вложение:", "XFILE", '');
                $edit->back = $app;
                $edit->save("xajax_SaveEvents(xajax.getFormValues(this.id))");
                $edit->addButton("Отменить", "load('$app')");
                $edit->showTable();
            }
            $list = new listTable($this->resId);
            $list->addSearch("Тип", "e.type", "LIST");
            $list->sqlSearch[] = $type;
            $list->addSearch("Тема письма", "e.title", "TEXT");
            $list->addSearch("Текст письма", "e.body", "TEXT");
            $list->SQL = "SELECT e.id,
                        e.type,
                        e.title,
                        ef.id AS img,
                        e.is_active_sw
						FROM mod_staff_events AS e
						LEFT JOIN mod_staff_events_files AS ef ON ef.refid = e.id
						WHERE 1=1 ADD_SEARCH";
            $list->addColumn("Тип", "", "TEXT");
            $list->addColumn("Тема письма", "", "TEXT");
            $list->addColumn("Вложение", "1%", "HTML", "style='text-align:center;'");
            $list->addColumn("", "1%", "STATUS_INLINE", "mod_staff_events.is_active_sw");
            $list->addURL 			= $app . "&edit=0";
            $list->editURL 			= $app . "&edit=TCOL_00";
            $list->deleteKey		= "mod_staff_events.id";
            $list->getData();
            foreach ($list->data as $k => $v) {
                $list->data[$k][1] = $type[$list->data[$k][1]];
                if ($list->data[$k][3]) {
                    $list->data[$k][3] = "<img src='index.php?module=admin&loc=core&action=handler&thumbid={$list->data[$k][3]}&t=mod_staff_events'>";
                }
            }
            $list->showTable();
        }

        $tab->endContainer();

    }

    /**
     * Названия методов которые могут использоваться в модуле "Планировщик"
     * @return array
     */
    public function getCronMethods() {
        return array(
            'checkEvents'
        );
    }
}
