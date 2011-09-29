<?php

/**
 * Мениджър за сензори
 */
class sens_Sensors extends core_Manager
{
    /**
     *  Необходими мениджъри
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_State,
                     Params=sens_Params, sens_Wrapper';
    
    
    /**
     *  Титла
     */
    var $title = 'Сензори';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'sens, admin';
    
    
    /**
     *  Права за запис
     */
    var $canRead = 'sens, admin';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('title', 'varchar(255)', 'caption=Заглавие, mandatory');
//        $this->FLD('params', 'text', 'caption=Инициализация');
//        $this->FLD('checkPeriod', 'int', 'caption=период (m)');
//        $this->FLD('monitored', 'keylist(mvc=sens_Params,select=param)', 'caption=Параметри');
        $this->FLD('location', 'key(mvc=common_Locations,select=title)', 'caption=Локация');
        $this->FLD('driver', 'class(interface=sens_DriverIntf)', 'caption=Драйвер,mandatory');
        $this->FLD('state', 'enum(active=Активен, closed=Спрян)', 'caption=Статус');
        $this->FNC('settings', 'varchar(255)', 'caption=Настройки');
        $this->FNC('results', 'varchar(255)', 'caption=Показания');
    }
    
    
   /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {

    }
   
   
   /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $rec, $data)
    {
		if (isset($rec->form->rec->id)) {
		}
    }
    
    /**
     * 
     * Enter description here ...
     */
	function act_Settings()
	{
        requireRole('admin');
        
        $form = cls::get('core_Form'); 
        
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $retUrl = getRetUrl()?getRetUrl():array($this);
        
        $driver = cls::get($rec->driver, array('id'=>$id));
        
        permanent_Settings::init($driver);
        
        $driver->prepareSettingsForm($form);
        
        $form->toolbar->addSbBtn('Запис', 'save', array('class' => 'btn-save'));
        $form->toolbar->addBtn('Отказ', $retUrl, array('class' => 'btn-cancel'));
        
        $form->input();
        
        if($form->isSubmitted()) {
        	$settings['fromForm'] = $form->rec;
        	$settings['values'] = $driver->getData();
			permanent_Data::write($driver->getSettingsKey(), $settings);
                
            return new Redirect($retUrl);
        }
        
        $form->title = tr("Настройка на сензор") . " \"" . $this->getVerbal($rec, 'title') .
        " - " . $this->getVerbal($rec, 'location') . "\"";
        $form->setDefaults($driver->settings['fromForm']);
        
        $tpl = $form->renderHtml();
        
        return $this->renderWrapping($tpl);
        
		
	}
	
    
    
    /**
     * Показваме актуални данни за всеки от параметрите
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {   

    	/**
         * @todo: Да се махне долния пасаж, когато се направи де-иснталиране
         */
        if(!cls::getClassName($rec->driver, FALSE)) {
            return;
        }

        $driver = cls::get($rec->driver, array('id'=>$rec->id));
       
        $sensorData = array();

        // Изваждаме данните за този сензор
        permanent_Settings::init($driver);

        $settingsArr = (array)$driver->settings['fromForm'];
        
        foreach ($settingsArr as $name =>$value) {
        	$row->settings .= $name . " = " . $value. "<br>" ;
        }

        $row->settings .= "<br>" . permanent_Settings::getLink($driver) ;
        
        //bp($driver->settings['values']);
        foreach ($driver->params as $param => $properties) {
        	$row->results .= "{$param} = {$driver->settings['values'][$param]} {$properties['details']}<br>";	
        }
         
        return;
    }
    
    
    /**
     * 
     * Стартира функцията за крона през ВЕБ
     */
    function act_Cron()
    {
    	$this->cron_Process();
    }
    
    
    /**
     * 
     * Стартира се на всяка минута от cron-a
     * Извиква по http sens_Sensors->act_Process
     * за всеки 1 драйвер като предава id и key - ключ,
     * базиран на id на драйвера и сол 
     */
    function cron_Process()
    {
    	$querySensors = sens_Sensors::getQuery();
    	$querySensors->where("#state='active'");
    	$querySensors->show("id");
    	while ($sensorRec = $querySensors->fetch($where)) {
    		$url = toUrl(array($this->className,'Process',str::addHash($sensorRec->id)), 'absolute');
    		file_get_contents($url,FALSE,NULL,0,0);
    	}
    }
    
    /**
     * 
     * Приема id и key - базиран на драйвера и сол
     * Затваря връзката с извикващия преждевременно.
     * Инициализира обект $driver
     * и извиква $driver->process().
     * 
     */
    function act_Process()
    {
    	/** Затваряме връзката с извиквача
    	 *	(Ако е функцията file_get_contents трябва да е нагласена
    	 *	да чете брой не повече от колкото се връщат оттук)
    	 */

		// Следващият ред генерира notice,
		// но без него file_get_contents забива, ако трябва да връща повече от 0 байта
		//ob_end_clean();
		
    	header("Connection: close\r\n");
		header("Content-Encoding: none\r\n");
		ob_start();
//		echo "OK";
//		$size = ob_get_length();
//		header("Content-Length: $size");
		header("Content-Length: 0");
		ob_end_flush();
		flush();
		ob_end_clean();
		
		$id = str::checkHash(Request::get('id','varchar'));
		
		if (FALSE === $id) {
			/**
			 * @todo Логва се съобщение за неоторизирано извикване
			 */
			exit(1);
		}
		$rec = $this->fetch("#id = $id");
        $driver = cls::get($rec->driver, array('id'=>$id));
		permanent_Settings::init($driver); 
        $driver->process();
    }
}