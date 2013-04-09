<?php


/**
 * Клас 'cal_Tasks' - Документ - задача
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Tasks extends core_Master
{
    
    
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, cal_Wrapper, doc_DocumentPlg, doc_ActivatePlg, plg_Printing, doc_SharablePlg, plg_Search, change_Plugin';
    

    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'timeStart';
    

    /**
     * Какви детайли има този мастер
     */
    var $details = 'cal_TaskProgresses';

    /**
     * Заглавие
     */
    var $title = "Задачи";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Задача";
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, timeStart, timeEnd, timeDuration, progress, sharedUsers';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'title, description';
	
	
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    var $rowToolsSingleField = 'title';
 
    
    /**
     * Кой може да чете?
     */
    var $canRead = 'powerUser';
    
    /**
     * Кой може да отлага задачата?
     */    
    var $canPostpone = 'powerUser';
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'powerUser';
    
    
    /**
     * Кой има право да приключва?
     */
    var $canChangeTaskState = 'powerUser';
    
    
    /**
     * Кой има право да затваря задачите?
     */
    var $canClose = 'powerUser';
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/task-normal.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'cal/tpl/SingleLayoutTasks.shtml';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Tsk";
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "1.3|Общи"; 
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title',    'varchar(128)', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('priority', 'enum(low=нисък,
                                    normal=нормален,
                                    high=висок,
                                    critical=критичен)', 
            'caption=Приоритет,mandatory,maxRadio=4,columns=4,notNull,value=normal');
        $this->FLD('description',     'richtext(bucket=calTasks)', 'caption=Описание,mandatory');

        // Споделяне
        $this->FLD('sharedUsers', 'userList', 'caption=Отговорници,mandatory');
        
        // Начало на задачата
        $this->FLD('timeStart', 'datetime', 'caption=Времена->Начало, silent, changable');
        
        // Продължителност на задачата
        $this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност, changable');
        
        // Краен срок на задачата
        $this->FLD('timeEnd', 'datetime',     'caption=Времена->Край, changable');
        
        // Изпратена ли е нотификация?
        $this->FLD('notifySent', 'enum(no,yes)', 'caption=Изпратена нотификация,notNull,input=none');
        
        // Дали началото на задачата не е точно определено в рамките на деня?
        $this->FLD('allDay', 'enum(no,yes)',     'caption=Цял ден?,input=none');
        
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)',     'caption=Прогрес,input=none,notNull,value=0');
        
        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time',     'caption=Отработено време,input=none');
    }


    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$cu = core_Users::getCurrent();
        $data->form->setDefault('priority', 'normal');
        $data->form->setDefault('sharedUsers', "|".$cu."|");

        $rec = $data->form->rec;
 
        if($rec->allDay == 'yes') {
            list($rec->timeStart,) = explode(' ', $rec->timeStart);
        }
        
    }


    /**
     * Подготвяне на вербалните стойности
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $blue = new color_Object("#2244cc");
        $grey = new color_Object("#bbb");

        $progressPx = min(100, round(100 * $rec->progress));
        $progressRemainPx = 100 - $progressPx;
        $row->progressBar = "<div style='display:inline-block;top:-5px;border-bottom:solid 10px {$blue}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'> </div>";
        
        if($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
            $rec->remainingTime = round((dt::mysql2timestamp($rec->timeEnd) - time()) / 60) * 60;
            $typeTime = cls::get('type_Time');
            if($rec->remainingTime > 0) {
                $row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
            } else {
                 $row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';
            }
        }
 
      
        $grey->setGradient($blue, $rec->progress);
 
        $row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";
    }


    /**
     * Показване на задачите в портала
     */
    static function renderPortal($userId = NULL)
    {

        if(empty($userId)) {
            $userId = core_Users::getCurrent();
        }
                
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Създаваме заявката
        $data->query = self::getQuery();
        
        // Подготвяме полетата за показване
        $data->listFields = 'timeStart,title,progress';

        $now = dt::verbal2mysql();
        
        $data->query->where("#sharedUsers LIKE '%|{$userId}|%' AND (#timeStart < '{$now}' || #timeStart IS NULL)");
        $data->query->where("#state = 'active'");
        $data->query->orderBy("timeStart=DESC");
        
        // Подготвяме навигацията по страници
        self::prepareListPager($data);
        
        // Подготвяме филтър формата
        self::prepareListFilter($data);
        
        // Подготвяме записите за таблицата
        self::prepareListRecs($data);
 
        if (is_array($data->recs)) {
            foreach($data->recs  as   &$rec) {
                 $rec->state = '';
            }    
        }

        $Tasks = cls::get('cal_Tasks');

        $Tasks->load('bgerp_plg_GroupByDate');
        
        // Подготвяме редовете на таблицата
        self::prepareListRows($data);
        
        $tpl = new ET("
            [#PortalPagerTop#]
            [#PortalTable#]
          ");
        
        // Попълваме таблицата с редовете
        
    	if($data->listFilter && $data->pager->pagesCount > 1){
    		$formTpl = $data->listFilter->renderHtml();
    		$formTpl->removeBlocks();
    		$formTpl->removePlaces();
        	$tpl->append($formTpl, 'ListFilter');
        }
        
        $tpl->append(self::renderListTable($data), 'PortalTable');

        return $tpl;
    }


    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
  
        $rec->allDay = (strlen($rec->timeStart) == 10) ? 'yes' : 'no';

        if($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
            $form->setError('timeEnd', 'Не може крайния срок да е преди началото на задачата');
        }
    }

    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	$mvc->updateTaskToCalendar($rec->id);
    }


    /**
     *
     */
    static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if($data->rec->state == 'active') {
            $data->toolbar->addBtn('Прогрес', array('cal_TaskProgresses', 'add', 'taskId' => $data->rec->id, 'ret_url' => array('cal_Tasks', 'single', $data->rec->id)), 'ef_icon=img/16/progressbar.png');
            $data->toolbar->addBtn('Напомняне', array('cal_Reminders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), 'ef_icon=img/16/bell_clock2.png, row=2');
        }
        
    }


    /**
     * След изтриване на запис
     */
    static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {        
        foreach($query->getDeletedRecs() as $id => $rec) {
 
            // изтриваме всички записи за тази задача в календара
            $mvc->updateTaskToCalendar($rec->id);
        }
    }

    
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId)
    {
    	if($action == 'postpone'){
	    	if ($rec->id) {
	        	if ($rec->state !== 'active' || (!$rec->timeStart) ) { 
	                $requiredRoles = 'no_one';
	            }  else {
	                if(!haveRole('ceo') || ($userId !== $rec->createdBy) &&
	                !type_Keylist::isIn($userId, $rec->sharedUsers)) {
	                	
	                	$requiredRoles = 'no_one';
	                }
	            }
    	     }
         }
    }
    
    /**
     * Прилага филтъра, така че да се показват записите за определение потребител
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
    	$userId = core_Users::getCurrent();
        $data->query->orderBy("#timeStart=ASC,#state=DESC");
        
                
        if($data->listFilter->rec->selectedUsers) {
	           
	         if($data->listFilter->rec->selectedUsers != 'all_users') {
	                $data->query->likeKeylist('sharedUsers', $data->listFilter->rec->selectedUsers);
	               
	           }
            	
        } 
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
    	$cu = core_Users::getCurrent();
  
        
        // Добавяме поле във формата за търсене
       
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent', array('attr' => array('onchange' => 'this.form.submit();')));
                
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search, selectedUsers';
        
        $data->listFilter->input('selectedUsers', 'silent');
    }

    /**
     * Обновява информацията за задачата в календара
     */
    static function updateTaskToCalendar($id)
    {
        $rec = static::fetch($id);
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);

        // Начална дата
        $fromDate = "{$cYear}-01-01";

        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Префикс на клучовете за записите в календара от тази задача
        $prefix = "TSK-{$id}";

        // Подготвяме запис за началната дата
        if($rec->timeStart && $rec->timeStart >= $fromDate && $rec->timeStart <= $toDate && ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'draft')) {
            
            $calRec = new stdClass();
                
            // Ключ на събитието
            $calRec->key = $prefix . '-Start';
            
            // Начало на задачата
            $calRec->time = $rec->timeStart;
            
            // Дали е цял ден?
            $calRec->allDay = $rec->allDay;
            
            // Икона на записа
            $calRec->type  = 'task';

            // Заглавие за записа в календара
            $calRec->title = "{$rec->title}";

            // В чии календари да влезе?
            $calRec->users = $rec->sharedUsers;
            
            // Статус на задачата
            $calRec->state = $rec->state;

            // Какъв да е приоритета в числово изражение
            $calRec->priority = self::getNumbPriority($rec);

            // Url на задачата
            $calRec->url = toUrl(array('cal_Tasks', 'Single', $id), 'local'); 
            
            $events[] = $calRec;
        }
        
        // Подготвяме запис за Крайния срок
        if($rec->timeEnd && $rec->timeEnd >= $fromDate && $rec->timeEnd <= $toDate && ($rec->state == 'active' || $rec->state == 'closed') ) {
            
            $calRec = new stdClass();
                
            // Ключ на събитието
            $calRec->key = $prefix . '-End';
            
            // Начало на задачата
            $calRec->time = $rec->timeEnd;
            
            // Дали е цял ден?
            $calRec->allDay = $rec->allDay;
            
            // Икона на записа
            $calRec->type  = 'end-date';

            // Заглавие за записа в календара
            $calRec->title = "Краен срок за \"{$rec->title}\"";

            // В чии календари да влезе?
            $calRec->users = $rec->sharedUsers;
            
            // Статус на задачата
            $calRec->state = $rec->state;
            
            // Какъв да е приоритета в числово изражение
            $calRec->priority = self::getNumbPriority($rec) - 1;

            // Url на задачата
            $calRec->url = toUrl(array('cal_Tasks', 'Single', $id), 'local'); 
            
            $events[] = $calRec;
        }
  
        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }


    /**
     * Връща приоритета на задачата за отразяване в календара
     */
    static function getNumbPriority($rec)
    {
        if($rec->state == 'active') {

            switch($rec->priority) {
                case 'low':
                    $res = 100;
                    break;
                case 'normal':
                    $res = 200;
                    break;
                case 'high':
                    $res = 300;
                    break;
                case 'critical':
                    $res = 400;
                    break;
            }
        } else {

            $res = 0;
        }

        return $res;
    }



    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = $this->getVerbal($rec, 'title');
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->title;
        
        return $row;
    }
    
    
    /**
     * Връща иконата на документа
     */
    function getIcon_($id)
    {
        $rec = self::fetch($id);

        return "img/16/task-" . $rec->priority . ".png";
    }

    
    /**
     * Изпращане на нотификации за започването на задачите
     */
    function cron_SendNotifications()
    {
        $query = $this->getQuery();
        $now = dt::verbal2mysql();
        $query->where("#state = 'active'  AND #notifySent = 'no' AND #timeStart <= '{$now}'");
        
        while($rec = $query->fetch()) {
            list($date, $time) = explode(' ', $rec->timeStart);  
            if($time != '00:00:00') {
                $subscribedArr = type_Keylist::toArray($rec->sharedUsers); 
                if(count($subscribedArr)) { 
                    $message = "Стартирана е задачата \"" . $this->getVerbal($rec, 'title') . "\"";
                    $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                    $customUrl = array('cal_Tasks', 'single',  $rec->id);
                    $priority = 'normal';
                    foreach($subscribedArr as $userId) {  
                        if($userId > 0  &&  
                            doc_Threads::haveRightFor('single', $rec->threadId, $userId)) {
                            bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
                        }
                    }
                }
            }

            $rec->notifySent = 'yes';

            $this->save($rec, 'notifySent');
        }
    }


    /**
     * Изпълнява се след начално установяване
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $Cron = cls::get('core_Cron');
        
        $rec = new stdClass();
        $rec->systemId = "StartTasks";
        $rec->description = "Известява за стартирани задачи";
        $rec->controller = "cal_Tasks";
        $rec->action = "SendNotifications";
        $rec->period = 1;
        $rec->offset = 0;
        
        $Cron->addOnce($rec);
        
        $res .= "<li>Известяване за стартирани задачи по крон</li>";
        
        //Създаваме, кофа, където ще държим всички прикачени файлове в задачи
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('calTasks', 'Прикачени файлове в задачи', NULL, '104857600', 'user', 'user');
    }
}