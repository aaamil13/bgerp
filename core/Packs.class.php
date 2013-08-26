<?php



/**
 * Клас 'core_Packs' - Управление на пакети
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Packs extends core_Manager
{
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Управление на пакети';
    
    
    /**
     * Кой може да инсталира?
     */
    var $canInstall = 'admin';
    
    
    /**
     * Кои може да деинсталира?
     */
    var $canDeinstall = 'admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'admin';
	
	
    /**
     * По колко пакета да показва на страница
     */
    var $listItemsPerPage = 24;
    

    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id,name,install=Обновяване,config=Конфигуриране,deinstall=Премахване';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'identifier(32)', 'caption=Пакет,notNull');
        $this->FLD('version', 'double(decimals=2)', 'caption=Версия,input=none');
        $this->FLD('info', 'varchar(128)', 'caption=Информация,input=none');
        $this->FLD('startCtr', 'varchar(64)', 'caption=Стартов->Мениджър,input=none,column=none');
        $this->FLD('startAct', 'varchar(64)', 'caption=Стартов->Контролер,input=none,column=none');
        $this->FLD('deinstall', 'enum(no,yes)', 'caption=Деинсталиране,input=none,column=none');
        
        // Съхранение на данните за конфигурацията
        $this->FLD('configData', 'text', 'caption=Конфигурация->Данни,input=none,column=none');

        $this->load('plg_Created,plg_SystemWrapper');
        
        $this->setDbUnique('name');
    }
    
    
    /**
     * Начална точка за инсталиране на пакети
     */
    function act_Install()
    {
        
        $this->requireRightFor('install');
        
        $pack = Request::get('pack', 'identifier');
        
        if(!$pack) error('Missing pack name.');
        
        $res = $this->setupPack($pack);
        
        return $this->renderWrapping($res);
    }
    
    
    /**
     * Деинсталиране на пакет
     */
    function act_Deinstall()
    {
        $this->requireRightFor('deinstall');
        
        $pack = Request::get('pack', 'identifier');
        
        if(!$pack) error('Липсващ пакет', $pack);
        
        if(!$this->fetch("#name = '{$pack}'")) {
            error('Този пакет не е инсталиран', $pack);
        }
        
        if($this->fetch("(#name = '{$pack}') AND (#deinstall = 'yes')")) {
            
            $cls = $pack . "_Setup";
            
            if(cls::load($cls, TRUE)) {
                
                $setup = cls::get($cls);
                
                if(!method_exists($setup, 'deinstall')) {
                    $res = "<h2>Пакета <font color=\"\">'{$pack}'</font> няма деинсталатор.</h2>";
                } else {
                    $res = "<h2>Деинсталиране на пакета <font color=\"\">'{$pack}'</font></h2>";
                    $res .= (string) "<ul>" . $setup->deinstall() . "</ul>";
                }
            } else {
                $res = "<h2 style='color:red;''>Липсва кода на пакета <font color=\"\">'{$pack}'</font></h2>";
            }
        }
        
        // Общи действия по деинсталирането на пакета
        
        // Премахване от core_Interfaces
        core_Interfaces::deinstallPack($pack);
        
        // Скриване от core_Classes
        core_Classes::deinstallPack($pack);
        
        // Премахване от core_Cron
        core_Cron::deinstallPack($pack);
        
        // Премахване от core_Plugins
        core_Plugins::deinstallPack($pack);
        
        // Премахване на информацията за инсталацията
        $this->delete("#name = '{$pack}'");
        
        $res .= "<div>Успешно деинсталиране.</div>";
        
        return new Redirect(array($this), $res);
    }
    
    
    /**
     * Връща всички не-инсталирани пакети
     */
    function getNonInstalledPacks()
    {
        
        if(!$this->fetch("#name = 'core'")) {
            $path = EF_EF_PATH . "/core/Setup.class.php";
            
            if(file_exists($path)) {
                $opt['core'] = 'Ядро на EF "core"';
            }
        }
        
        $appDirs = $this->getSubDirs(EF_APP_PATH);
        
        $vendorDirs = $this->getSubDirs(EF_VENDORS_PATH);
        
        $efDirs = $this->getSubDirs(EF_EF_PATH);
        
        if(defined('EF_PRIVATE_PATH')) {
            $privateDirs = $this->getSubDirs(EF_PRIVATE_PATH);
        }
        
        if (count($appDirs)) {
            foreach($appDirs as $dir => $dummy) {
                $path = EF_APP_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if(file_exists($path)) {
                    unset($vendorDirs[$dir]);
                    unset($efDirs[$dir]);
                    
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = 'Компонент на приложението "' . $dir . '"';
                    }
                }
            }
        }
        
        if (count($vendorDirs)) {
            foreach($vendorDirs as $dir => $dummy) {
                $path = EF_VENDORS_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if(file_exists($path)) {
                    unset($efDirs[$dir]);
                    
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = 'Публичен компонент "' . $dir . '"';
                    }
                }
            }
        }
        
        if (count($efDirs)) {
            foreach($efDirs as $dir => $dummy) {
                $path = EF_EF_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if(file_exists($path)) {
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = 'Компонент на фреймуърка "' . $dir . '"';
                    }
                }
            }
        }
        
        if (count($privateDirs)) {
            foreach($privateDirs as $dir => $dummy) {
                $path = EF_PRIVATE_PATH . "/" . $dir . "/" . "Setup.class.php";
                
                if(file_exists($path)) {
                    // Ако този пакет не е инсталиран - 
                    // добавяме го като опция за инсталиране
                    if(!$this->fetch("#name = '{$dir}'")) {
                        $opt[$dir] = 'Собствен компонент "' . $dir . '"';
                    }
                }
            }
        }
        
        return $opt;
    }
    
    
    /**
     * Изпълнява се преди извличането на редовете за листови изглед
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy("#name");
    }
    
    
    /**
     * Рендира лентата с инструменти за списъчния изглед
     */
    function renderListToolbar_($data)
    {
        if(! ($opt = $this->getNonInstalledPacks())) return "";
        
        $form = cls::get('core_Form', array('view' => 'horizontal'));
        $form->FNC('pack', 'varchar', 'caption=Пакет,input');
        
        $form->setOptions('pack', $opt);
        $form->toolbar = cls::get('core_Toolbar');
        $form->setHidden(array('Act' => 'install'));
        $form->toolbar->addSbBtn('Инсталирай', 'default', 'ef_icon = img/16/install.png');
        $form->toolbar->addBtn('Обновяване на системата', array("core_Packs", "systemUpdate"), 'ef_icon = img/16/install.png');
        
        return $form->renderHtml();
    }
    
    
    /**
     * Връща съдържанието на кеша за посочения обект
     */
    function getSubDirs($dir)
    {
        $dirs = array();
        
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                
                while ($file = readdir($dh)) {
                    
                    if ($file == "." || $file == "..") continue;
                    
                    if(is_dir($dir . "/" . $file)) {
                        $dirs[$file] = TRUE;
                    }
                }
            } else {
                bp("Can't open dir", $dir, $dh);
            }
        }
        
        return $dirs;
    }
    
    
    /**
     * След конвертирането на един ред от вътрешно към вербално представяне
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        // Показва пореден, вместо ID номер
        static $rowNum;
        $rowNum++;
        $row->id = $rowNum;
        
        $row->name = "<b>" . $mvc->getVerbal($rec, 'name') . "</b>";
         
        $row->name = new ET($row->name);
        $row->name->append(' ' . str_replace(',', '.', $rec->version));
        if($rec->startCtr) {
        	$row->name = ht::createLink($row->name, array($rec->startCtr, $rec->startAct), NULL, "class=pack-title");
        }
        $row->name .= "<div><small>{$rec->info}</small></div>";
        
        $imageUrl = sbf("img/100/default.png","");
        
        $filePath = getFullPath("{$rec->name}/icon.png");

        if($filePath){
       		$imageUrl = sbf("{$rec->name}/icon.png","");
       	}
       	
       	$row->img = ht::createElement("img", array('src' => $imageUrl));
       	
       	if($rec->startCtr) {
       		$row->img = ht::createLink($row->img, array($rec->startCtr, $rec->startAct));
       	}
       	
        $row->install = ht::createLink(tr("Обновяване"), array($mvc, 'install', 'pack' => $rec->name), NULL, array('id'=>$rec->name."-install"));
        
        if($rec->deinstall == 'yes') {
           $row->deinstall = ht::createLink(tr("Оттегляне"), array($mvc, 'deinstall', 'pack' => $rec->name), NULL, array('id'=>$rec->name."-deinstall"));
        } else {
           $row->deinstall = "";
        }
        
        try {
            $conf = self::getConfig($rec->name);
        } catch (core_exception_Expect $e) {
            $row->install = 'Липсва кода на пакета!';
            $row->ROW_ATTR['style'] = 'background-color:red';
            return;
        }
       

        if($conf->getConstCnt()) {
            $row->config = ht::createLink(tr("Настройки"), array($mvc, 'config', 'pack' => $rec->name), NULL, array('id'=>$rec->name."-config"));
        }

        if($conf->haveErrors()) {

            $row->ROW_ATTR['style'] = 'background-color:red';
        }
    }
    
    
    /**
     * Проверява:
     * (1) дали таблицата на този модел съществува
     * (2) дали е установен пакета 'core'
     * (3) дали е установен пакета EF_APP_CODE_NAME
     * което и да не е изпълнено - предизвиква начално установяване
     */
    function checkSetup()
    {
        static $semafor;
        
        if($semafor) return;
        
        $semafor = TRUE;
        
        if(!$this->db->tableExists($this->dbTableName)) {
            $this->firstSetup();
        } elseif(!$this->fetch("#name = 'core'") ||
            (!$this->fetch("#name = '" . EF_APP_CODE_NAME . "'") && cls::load(EF_APP_CODE_NAME . "_Setup", TRUE))) {
            $this->firstSetup();
        } else {

            return TRUE;
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Setup()
    {
        if(isDebug()) {
            return $this->firstSetup(array('Index'));
        }
    }
    
    
    /**
     * Тази функция получава управлението само след първото стартиране
     * на системата. Нейната задача е да направи начално установяване
     * на ядрото на системата и заглавния пакет от приложението
     */
    function firstSetup($nextUrl = NULL)
    {
        $res = $this->setupPack('core');
        
        $res .= $this->setupPack(EF_APP_CODE_NAME);
        
        $html = "<html><head>";
        
        // Редиректваме към Users->add, с връщане към текущата страница
        $Users = cls::get('core_Users');
        
        if(!$nextUrl) {
            // Ако нямаме нито един потребител, редиректваме за добавяне на администратор
            if(!$Users->fetch('1=1')) {
                $url = array('core_Users', 'add', 'ret_url' => TRUE);
            } else {
                $url = getCurrentUrl();
            }
        } else {
            $url = $nextUrl;
        }

        $url = toUrl($url);
        
        $html .= "<meta http-equiv='refresh' content='15;url={$url}' />";
        
        $html .= "<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />";
        $html .= "</head><body>";
        
        $html .= $res;
        
        $html .= "</body></html>";
        
        echo $html;
        
        shutdown();
    }
    
    
    /**
     * Прави начално установяване на посочения пакет. Ако в
     * Setup-а на пакета е указано, че той зависи от други пакети
     * (var $depends = ... ), прави се опит и те да се установят
     */
    function setupPack($pack, $version = 0, $force = TRUE)
    {
        // Максиламно време за инсталиране на пакет
        set_time_limit(400);
        
        static $f = 0;
        
        DEBUG::startTimer("Инсталиране на пакет '{$pack}'");
        
        // Имената на пакетите са винаги с малки букви
        $pack = strtolower($pack);
        
        // Предпазване срещу рекурсивно зацикляне
        if($this->alreadySetup[$pack . $force]) return;
        
        // Отбелязваме, че на текущия хит, този пакет е установен
        $this->alreadySetup[$pack . $force] = TRUE;

        GLOBAL $setupFlag;
        
        // Ако е пуснат от сетъп-а записваме в Лог-а 
        if($setupFlag) {
        	file_put_contents(EF_TEMP_PATH . '/setupLog.html', "<h2>Инсталиране на {$pack} ... <h2>", FILE_APPEND|LOCK_EX);
        }
        
        // Проверка дали Setup класа съществува
        if(!cls::load($pack . "_Setup", TRUE)) {
            return "<h4>Невъзможност да се инсталира <font color='red'>{$pack}</font>. " .
            "Липсва <font color='red'>Setup</font> клас.</h4>";
        }
        
        // Вземаме Setup класа, за дадения пакет
        $setup = cls::get($pack . '_Setup');
        
        // Ако има зависимости, проследяваме ги
        // Първо инсталираме зависимостите
        if($setup->depends) {
            $depends = arr::make($setup->depends, TRUE);
            
            foreach($depends as $p => $v) {
                $res .= $this->setupPack($p, $v, FALSE);
            }
        }

        // Започваме самото инсталиране
        if($setup->startCtr && !$setupFlag) {
            $res .= "<h2>Инсталиране на пакета \"<a href=\"" .
            toUrl(array($setup->startCtr, $setup->startAct)) . "\"><b>{$pack}</b></a>\"&nbsp;";
        } else {
            $res .= "<h2>Инсталиране на пакета \"<b>{$pack}</b>\"&nbsp;";
        }

        try {
            $conf = self::getConfig($pack);
            if($conf->getConstCnt() && !$setupFlag) {  
               $res .= ht::createBtn("Конфигуриране", array('core_Packs', 'config', 'pack' => $pack), NULL, NULL, 'class=btn-settings');
            }
        } catch (core_exception_Expect $e) {}

        $res .= '</h2>';
        
        
        $res .= "<ul>";
        
        // Единственото, което правим, когато версията, която инсталираме
        // е по-малка от изискваната, е да сигнализираме за този факт
        if($version > 0 && $version > $setup->version) {
            $res .= "<li style='color:red'>За пакета '{$pack}' се изисква версия [{$version}], " .
            "а наличната е [{$setup->version}]</li>";
        }
        
        // Ако инсталирането е форсирано 
        //   или този пакет не е инсталиран до сега 
        //   или инсталираната версия е различна спрямо тази
        // извършваме инсталационна процедура
        if(!$force) {
            $rec = $this->fetch("#name = '{$pack}'");
        }
        
        if($force || empty($rec) || ($rec->version != $setup->version)) {
            
            // Форсираме системния потребител
            core_Users::forceSystemUser();
            
            // Форсираме Full инсталиране, ако имаме промяна на версиите
            if($rec && ($rec->version != $setup->version)) {
                Request::push(array('Full' => 1), 'full');
            }
 
            // Правим началното установяване
            $res .= $setup->install();
            Request::pop('full');

            // Де-форсираме системния потребител
            core_Users::cancelSystemUser();
            
            $rec = $this->fetch("#name = '{$pack}'");
            
            // Правим запис на факта, че пакетът е инсталиран
            if(!is_object($rec)) $rec = new stdClass();
            $rec->name = $pack;
            $rec->version = $setup->version;
            $rec->info = $setup->info;
            $rec->startCtr = $setup->startCtr;
            $rec->startAct = $setup->startAct;
            $rec->deinstall = method_exists($setup, 'deinstall') ? 'yes' : 'no';
            $this->save($rec);
        } else {
            $res .= "<li>Пропускаме, има налична инсталация</li>";
        }
        
        
        $res .= "</ul>";
        
        if($setupFlag) {
			// Махаме <h2> тага на заглавието
			$res = substr($res, strpos($res, "</h2>"), strlen($res));
			file_put_contents(EF_TEMP_PATH . '/setupLog.html', $res, FILE_APPEND|LOCK_EX);
			unset($res);
        }
        
        DEBUG::stopTimer("Инсталиране на пакет '{$pack}'");
        
        if($setupFlag && $pack == 'bgerp') {
            shutdown();
        }

        return $res;
    }


	/**
     * Стартира обновяване на системата през УРЛ
     */
    function act_systemUpdate()
    {
		requireRole('admin');
		self::systemUpdate();
    }

    
    /**
     * Стартира обновяване на системата
     */
    function systemUpdate()
	{
		$SetupKey = setupKey();
		//$SetupKey = md5(BGERP_SETUP_KEY . round(time()/10));
		
		redirect(array("core_Packs", "systemUpdate", SetupKey=>$SetupKey, "step"=>2, "bgerp"=>1));
	}    


    /****************************************************************************************
     *                                                                                      *
     *     Функции за работа с конфигурацията                                               *
     *                                                                                      *
     ****************************************************************************************/

    /**
     * Връща конфигурационните данни за даден пакет
     */
    static function getConfig($packName) 
    {
        $rec = static::fetch("#name = '{$packName}'");
        $setup = cls::get("{$packName}_Setup");

        // В Setup-a се очаква $configDesctiption в следната структура:
        // Полета за конфигурационни променливи на пакета
        // Описание на конфигурацията: 
        // array('CONSTANT_NAME' => array($type, 
        //                                $params, 
        //                                'options' => $options, 
        //                                'suggestions' => $suggestions, 
        //        'CONSTANT_NAME2' => .....

        $conf = new core_ObjectConfiguration($setup->configDescription, $rec->configData);

        return $conf;
    }




    /**
     * Конфирурира даден пакет
     */
    function act_Config()
    {
        requireRole('admin');

        expect($packName = Request::get('pack', 'identifier'));
        
        $rec = static::fetch("#name = '{$packName}'");
        
        $cls = $packName . "_Setup";
            
        if(cls::load($cls, TRUE)) {
            $setup = cls::get($cls);
        } else {
            error("Липсваш клас $cls");
        }
        
        if($setup->configDescription) {
            $description = $setup->configDescription;
        } else {
            error("Пакета $pack няма нищо за конфигуриране");
        }
        
        if($rec->configData) {
            $data = unserialize($rec->configData);
        } else {
            $data = array();
        }
 
        $form = cls::get('core_Form');

        $form->title = "Настройки на пакета |*<b style='color:green;'>{$packName}<//b>";

        foreach($description as $field => $params) {
            $attr = arr::make($params[1], TRUE);
            $attr['input'] = 'input';

            setIfNot($attr['caption'], '|*' . $field);

            if(defined($field) && $data[$field]) {
                    $attr['hint'] .= ($attr['hint'] ? "\n" : '') . 'Стойност по подразбиране|*: "' . constant($field) . '"';
            }

            $form->FNC($field, $params[0], $attr);
            if($data[$field]) { 
                $form->setDefault($field, $data[$field]);
            } elseif(defined($field)) {
                $form->setDefault($field, constant($field));
                $form->setField($field, array('attr' => array('style' => 'color:#999;')));
            }
        }

        $form->setHidden('pack', $rec->name);

        $form->input();

        if($form->isSubmitted()) {
            
            // $data = array();

            foreach($description as $field => $params) {
                $sysDefault = defined($field) ? constant($field) : '';
                if($sysDefault != $form->rec->{$field}) { //bp($field, constant($field), $data[$field], BGERP_BLAST_SUCCESS_ADD);
                    $data[$field] = $form->rec->{$field};
                } else {
                    $data[$field] = '';
                }
            }

            $id = self::setConfig($packName, $data);
        
            // Правим запис в лога
            $this->log($data->cmd, $rec->id, "Промяна на конфигурацията на пакет {$packName}");
            
            return new Redirect(array($this));
        }
        
        $form->toolbar->addSbBtn('Запис', 'default', 'ef_icon = img/16/disk.png');

        // Добавяне на допълнителни системни действия
        if(count($setup->systemActions)) {
            foreach($setup->systemActions as $name => $url) {
                $form->toolbar->addBtn($name, $url);
            }
        }

        $form->toolbar->addBtn('Отказ', array($this),  'ef_icon = img/16/close16.png');

        return $this->renderWrapping($form->renderHtml());

    }
    

    /**
     * Задава конфигурация на пакет
	 *
     * @param string $name
     * @param array  $data
     */
    static function setConfig($name, $data)
    {
    	$rec = self::fetch("#name = '{$name}'");
    	if(!$rec) {
    		$rec = new stdClass();
    		$rec->name = $name;
    	}
    	
    	if($rec->configData) {
    		$exData = unserialize($rec->configData);
    	} else {
    		$exData = array();
    	}
    	
    	if(count($data)) {
    		foreach($data as $key => $value) {
                $exData[$key] = $value;
    		}
    	}
    	
    	$rec->configData = serialize($exData);
    	
    	return self::save($rec);   	
    }

    
    /**
     * Функция за преобразуване на стринга в константите в масив
     * 
     * @param string $conf - Данните, които ще се преобразуват
     * 
     * @return array $resArr - Масив с дефинираните константи
     */
    static function toArray($conf)
    {
        // Ако е масив
        if (is_array($conf)) {
            
            return $conf;
        }
        
        // Ако е празен стринг
        if (empty($conf)) {
            
            return array();
        }
        
        // Масив с всички стойности
        $cArr = explode(',', $conf);
        
        // Обхождаме масива
        foreach($cArr as $conf) {
            
            // Изчистваме празните интервали
            $conf = trim($conf);
            
            // Ако стринга не е празен
            if($conf !== '') {

                // Добавяме в масива
                $resArr[$conf] = $conf;
            }
        }
        
        return $resArr;
    }
    
    
    /**
     * Променяме Списъчния изглед на пакетите
     */
    function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	$res = new ET(getFileContent("core/tpl/ListPack.shtml"));
    	$blockTpl = $res->getBlock('ROW');
    	
    	foreach($data->rows as $row) {
    		$rowTpl = clone($blockTpl);
    		$rowTpl->placeObject($row);
    		$rowTpl->removeBlocks();
    		$rowTpl->append2master();
    	}
    	
    	return FALSE; 
    }
}