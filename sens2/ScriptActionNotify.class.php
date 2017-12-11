 <?php

/**
 * Действие на скрипт за изпращане на SMS
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sens2_ScriptActionNotify
{
    /**
     * Поддържани интерфейси
     */
    var $interfaces ="sens2_ScriptActionIntf";


    /**
     * Наименование на действието
     */
    var $title = 'Известяване на потребители';


    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с конфигурацията на контролера (IP, port, pass, ...)
     */
    function prepareActionForm(&$form)
    {
        
        $form->FLD('message', 'varchar', 'caption=Известяване->Съобщение,mandatory');
        $form->FLD('priority', 'enum(normal=Нормален, warning=Предупреждение, alert=Тревога)', 'caption=Известяване->Приоритет,mandatory');
        $form->FLD('users', 'userList', 'caption=Известяване->Потребители,mandatory');
        $form->FLD('cond', 'text(rows=2)', 'caption=Условие за да се изпрати->Израз,mandatory,width=100%');
        $form->FLD('periodLock', 'time(suggestions=2 часа|6 часа|8 часа|24 часа|48 часа)', 'caption=Не повече от един SMS за->Период');
        $form->FLD('beginBlock', 'time(suggestions=00:00|01:00|02:00|03:00|04:00|05:00|06:00|07:00|08:00|09:00|10:00' . '|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|19:00|20:00|21:00|22:00|23:00,format=H:M)', 'caption=Забранено време за изпращане->Начало');
        $form->FLD('endBlock', 'time(suggestions=00:00|01:00|02:00|03:00|04:00|05:00|06:00|07:00|08:00|09:00|10:00' . '|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00|19:00|20:00|21:00|22:00|23:00,format=H:M)', 'caption=Забранено време за изпращане->Край');
        
        
        $vars = sens2_ScriptDefinedVars::getContex($form->rec->scriptId);
        foreach($vars as $i => $v) {
            $suggestions[$i] = $i;
        }
        
        $inds = sens2_Indicators::getContex();
        foreach($inds as $i => $v) {
            $suggestions[$i] = $i;
        }

        asort($suggestions);
        $form->setSuggestions('cond', $suggestions);

        $form->setDefault('users', '|' . core_Users::getCurrent() . '|');
    }
   
    
    /**
     * Проверява след  субмитване формата с настройки на контролера
     * Тук контролера може да зададе грешки и предупреждения, в случай на 
     * некоректни конфигурационни данни използвайки $form->setError() и $form->setWarning()
     *
     * @param   core_Form   форма с въведени данни от заявката (след $form->input)
     */
    function checkActionForm($form)
    {
    }


    function toVerbal($rec)
    { 
        $cond   = sens2_Scripts::highliteExpr($rec->cond, $rec->scriptId);
        $UL = cls::get('type_UserList');
        $users = $UL->toVerbal($rec->users);

        $message = type_Varchar::escape($rec->message);
        
        $EN = core_Type::getByName("type_Enum(normal=Нормален, warning=Предупреждение, alert=Тревога)");
        $priority = $EN->toVerbal($rec->priority);
     
        $res = "Известие ({$priority}) <span style=\"color:green\">`{$message}`</span> към {$users}, ако {$cond}";
 
        return $res;
    }



    /**
     * Извършва действието, с параметрите, които са в $rec
     */
    function run($rec)
    {
        // Ако има условие и то не е изпълнено - не правим нищо
        if(trim($rec->cond)) {
            $cond = sens2_Scripts::calcExpr($rec->cond, $rec->scriptId);
            if($cond === sens2_Scripts::CALC_ERROR) {

                return 'stopped';
            }
            if(!$cond) {

                return 'closed';
            }
        }

        // Проверяваме дали е удобно да се пращат SMS-и по това време

        // Задаваме го на изхода
        $userList = keylist::toArray($rec->users);
        foreach($userList as $userId) {
            $res = bgerp_Notifications::add($rec->message, array('sens2_Scripts', 'Single', $rec->scriptId), $userId, $rec->priority);
        }
                
         
        if($res !== FALSE) {

            return 'active';
        } else {

            return 'stopped';
        }
    }
}