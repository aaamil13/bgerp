<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата на справка на баланса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_HistoryReport extends core_Manager
{
    
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'ceo, acc';
    
	
	/**
	 * Заглавие
	 */
    public $title = 'Хронологична счетоводна справка';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'Balance=acc_BalanceDetails, acc_Wrapper';
    
    
    /**
     * Брой записи от историята на страница
     */
    public $listHistoryItemsPerPage = 40;
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function prepareReportForm(&$form)
    {
    	$form->FLD('accountId','acc_type_Account(allowEmpty)', 'input,caption=Сметка,silent,mandatory', array('attr' => array('onchange' => "addCmdRefresh(this.form);this.form.submit()")));
    	$form->FLD('fromDate', 'date(allowEmpty)', 'caption=От,input,width=15em,mandatory');
    	$form->FLD('toDate', 'date(allowEmpty)', 'caption=До,input,width=15em,mandatory');
    	
    	$op = $this->getBalancePeriods();
    	
    	$form->setOptions('fromDate', array('' => '') + $op->fromOptions);
    	$form->setOptions('toDate', array('' => '') + $op->toOptions);
    	
    	if($form instanceof core_Form){
    		$form->input();
    	}
    	
    	if(isset($form->rec->accountId)){
    		if($form->rec->id){
    			if(frame_Reports::fetchField($form->rec->id, 'filter')->accountId != $form->rec->filter->accountId){
    				unset($form->rec->ent1Id,$form->rec->ent2Id,$form->rec->ent3Id);
    				unset($form->rec->filter->ent1Id,$form->rec->filter->ent2Id,$form->rec->filter->ent3Id);
    			}
    		}
    		
    		$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
    		if(count($accInfo->groups)){
    			foreach ($accInfo->groups as $i => $gr){
    				$form->FNC("ent{$i}Id", "acc_type_Item(lists={$gr->rec->num}, allowEmpty)", "caption=Избор на пера->{$gr->rec->name},input,mandatory");
    			}
    		}
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function checkReportForm(&$form)
    {
    	if($form->isSubmitted()){
    		if($form->rec->toDate < $form->rec->fromDate){
    			$form->setError('to, from', 'Началната дата трябва да е по малка от крайната');
    		}
    	}
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function prepareReportData($filter)
    {
    	// Подготвяне на данните
    	$data = new stdClass();
    	$accNum = acc_Accounts::fetchField($filter->accountId, 'num');
    	
    	$data->rec = new stdClass();
    	$data->rec->accountId = $filter->accountId;
    	$data->rec->ent1Id = $filter->ent1Id;
    	$data->rec->ent2Id = $filter->ent2Id;
    	$data->rec->ent3Id = $filter->ent3Id;
    	$data->rec->accountNum = $accNum;
    	
    	acc_BalanceDetails::requireRightFor('history', $data->rec);
    	
    	$balanceRec = $this->getBalanceBetween($from, $to);
    	$data->balanceRec = $balanceRec;
    	$data->fromDate = $filter->fromDate;
    	$data->toDate = $filter->toDate;
    	
    	$this->prepareHistory($data);
    	
    	return $data;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function renderReportData($filter, $data)
    {
    	return $this->renderHistory($data);
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see frame_ReportSourceIntf)
     */
    public function canSelectSource($userId = NULL)
    {
    	return core_Users::haveRole($this->canSelectSource, $userId);
    }
    
    
    public function act_History()
    {
    	$this->requireRightFor('history');
    	$this->currentTab = 'Хронология';
    	 
    	expect($accNum = Request::get('accNum', 'int'));
    	expect($accId = acc_Accounts::fetchField("#num = '{$accNum}'", 'id'));
    	 
    	$from = Request::get('fromDate');
    	$to = Request::get('toDate');
    	 
    	$ent1 = Request::get('ent1Id', 'int');
    	if($ent1){
    		expect(acc_Items::fetch($ent1));
    	}
    	 
    	$ent2 = Request::get('ent2Id', 'int');
    	if($ent2){
    		expect(acc_Items::fetch($ent2));
    	}
    	 
    	$ent3 = Request::get('ent3Id', 'int');
    	if($ent3){
    		expect(acc_Items::fetch($ent3));
    	}
    	 
    	$this->title = 'Хронологична справка';
    	 
    	// Подготвяне на данните
    	$data = new stdClass();
    	 
    	$balanceRec = $this->getBalanceBetween($from, $to);
    	
    	$data->rec = new stdClass();
    	$data->rec->accountId = $accId;
    	$data->rec->ent1Id = $ent1;
    	$data->rec->ent2Id = $ent2;
    	$data->rec->ent3Id = $ent3;
    	$data->rec->accountNum = $accNum;
    	 
    	acc_BalanceDetails::requireRightFor('history', $data->rec);
    	 
    	$data->balanceRec = $balanceRec;
    	$data->fromDate = $from;
    	$data->toDate = $to;
    	
    	$this->prepareSingleToolbar($data);
    	
    	// Подготовка на филтъра
    	$this->prepareHistoryFilter($data);
    	
    	// Подготовка на историята
    	$this->prepareHistory($data);
    	
    	// Рендиране на историята
    	$tpl = $this->renderHistory($data);
    	$tpl = $this->renderWrapping($tpl);
    	 
    	// Връщаме шаблона
    	return $tpl;
    }
    
    private function prepareSingleToolbar($data)
    {
    	if(!Mode::is('printing')){
    		$data->toolbar = cls::get('core_Toolbar');
    		
    		if(acc_BalanceDetails::haveRightFor('history', $data->rec)){
    			 
    			// Бутон за отпечатване
    			$printUrl = getCurrentUrl();
    			$printUrl['Printing'] = 'yes';
    			$data->toolbar->addBtn('Печат', $printUrl, 'id=btnPrint,target=_blank,row=2', 'ef_icon = img/16/printer.png,title=Печат на страницата');
    		}
    		
    		if(acc_Balances::haveRightFor('read')){
    			if(empty($data->balanceRec->id)){
    				$data->toolbar->addBtn('Обобщена', $url, 'id=btnOverview,error=Невалиден период');
    			} else {
    				$data->toolbar->addBtn('Обобщена', array($this->Balance->Master, 'single', $data->balanceRec->id, 'accId' => $data->rec->accountId), FALSE, FALSE, "title=Обобщена оборотна ведомост");
    			}
    		}
    	}
    }
    
    private function getBalanceBetween($from, $to)
    {
    	$bQuery = acc_Balances::getQuery();
    	$bQuery->where("#fromDate >= '{$from}' && #toDate <= '{$to}'");
    	$bQuery->orderBy('id', 'ASC');
    	if($balanceId = $bQuery->fetch()->id){
    		
    		return acc_Balances::fetch($balanceId);
    	}
    	
    	return FALSE;
    }
    
    
    private function getBalancePeriods()
    {
    	// За начална и крайна дата, слагаме по подразбиране, датите на периодите
    	// за които има изчислени оборотни ведомости
    	$balanceQuery = acc_Balances::getQuery();
    	$balanceQuery->orderBy("#fromDate", "ASC");
    	
    	$optionsFrom = $optionsTo = array();
    	while($bRec = $balanceQuery->fetch()){
    		$bRow = acc_Balances::recToVerbal($bRec, 'periodId,id,fromDate,toDate,-single');
    		$optionsFrom[$bRec->fromDate] = $bRow->periodId . " ({$bRow->fromDate})";
    		$optionsTo[$bRec->toDate] = $bRow->periodId . " ({$bRow->toDate})";
    	}
    	
    	return (object)array('fromOptions' => $optionsFrom, 'toOptions' => $optionsTo);
    }
    
    
    /**
     * Подготвя филтъра на историята на перата
     */
    private function prepareHistoryFilter(&$data)
    {
    	$data->listFilter = cls::get('core_Form');
    	$data->listFilter->method = 'GET';
    	$filter = &$data->listFilter;
    	$filter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$filter->class = 'simpleForm';
    	 
    	$filter->FNC('fromDate', 'date', 'caption=От,input,width=15em');
    	$filter->FNC('toDate', 'date', 'caption=До,input,width=15em');
    	$filter->FNC('accNum', 'int', 'input=hidden');
    	$filter->FNC('ent1Id', 'int', 'input=hidden');
    	$filter->FNC('ent2Id', 'int', 'input=hidden');
    	$filter->FNC('ent3Id', 'int', 'input=hidden');
    	$filter->showFields = 'fromDate,toDate';
    	 
    	$filter->setDefault('accNum', $data->rec->accountNum);
    	$filter->setDefault('ent1Id', $data->rec->ent1Id);
    	$filter->setDefault('ent2Id', $data->rec->ent2Id);
    	$filter->setDefault('ent3Id', $data->rec->ent3Id);
    	 
    	$op = $this->getBalancePeriods();
    	
    	$filter->setOptions('fromDate', $op->fromOptions);
    	$filter->setOptions('toDate', $op->toOptions);
    	$filter->setDefault('fromDate', $data->fromDate);
    	$filter->setDefault('toDate', $data->toDate);
    	 
    	// Активиране на филтъра
    	$filter->input();
    	if($filter->isSubmitted()){
    		if($filter->rec->fromDate > $filter->rec->toDate){
    			$filter->setError('fromDate,toDate', 'Началната дата е по-голяма от крайната');
    		}
    	}
    
    	// Ако има изпратени данни
    	if($filter->rec){
    		if($filter->rec->from){
    			$data->fromDate = $filter->rec->from;
    		}
    		 
    		if($filter->rec->to){
    			$data->toDate = $filter->rec->to;
    		}
    	}
    }
    
    
    /**
     * Подготовка на историята за перара
     *
     * @param stdClass $data
     */
    private function prepareHistory(&$data)
    {
    	$rec = &$data->rec;
    	$balanceRec = $data->balanceRec;
    	 
    	// Подготвяне на данните на записа
    	$Date = cls::get('type_Date');
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	 
    	// Подготовка на вербалното представяне
    	$row = new stdClass();
    	$row->accountId = acc_Accounts::getTitleById($rec->accountId);
    	$accountRec = acc_Accounts::fetch($rec->accountId);
    	 
    	foreach(range(1, 3) as $i){
    		if ($accountRec->{"groupId{$i}"} && $rec->{"ent{$i}Id"}) {
    			$row->{"ent{$i}Id"} = acc_Items::fetchField($rec->{"ent{$i}Id"}, 'titleLink');
    		}
    	}
    	 
    	$data->row = $row;
    	
    	// Подготовка на пейджъра
    	$this->listItemsPerPage = $this->listHistoryItemsPerPage;
    	$this->prepareListPager($data);
    	 
    	// Намиране на най-стария баланс можеш да послужи за основа на този
    	$balanceBefore = $this->Balance->Master->getBalanceBefore($data->fromDate);
    	
    	if($balanceBefore){
    		// Зареждаме баланса за посочения период с посочените сметки
    		$this->Balance->loadBalance($balanceBefore->id, $rec->accountNum, NULL, $rec->ent1Id, $rec->ent2Id, $rec->ent3Id);
    	}
    	 
    	
    	// Запомняне за кои пера ще показваме историята
    	$this->Balance->historyFor = array('accId' => $rec->accountId, 'item1' => $rec->ent1Id, 'item2' => $rec->ent2Id, 'item3' => $rec->ent3Id);
    	
    	// Извличане на всички записи към избрания период за посочените пера
    	$accSysId = acc_Accounts::fetchField($rec->accountId, 'systemId');
    	$this->Balance->prepareDetailedBalanceForPeriod($data->fromDate, $data->toDate, $accSysId, $rec->ent1Id, $rec->ent2Id, $rec->ent3Id, TRUE, $data->pager);
    	
    	$balance = $this->Balance->getCalcedBalance();
    	$b = $balance[$rec->accountId][$rec->ent1Id][$rec->ent2Id][$rec->ent3Id];
    	
    	$rec->baseAmount = $b['baseAmount'];
    	$rec->baseQuantity = $b['baseQuantity'];
    	 
    	$rec->blAmount = $b['blAmount'];
    	$rec->blQuantity = $b['blQuantity'];
    	$row->blAmount = $Double->toVerbal($rec->blAmount);
    	$row->blQuantity = $Double->toVerbal($rec->blQuantity);
    	 
    	$row->baseAmount = $Double->toVerbal($rec->baseAmount);
    	$row->baseQuantity = $Double->toVerbal($rec->baseQuantity);
    	 
    	if(round($rec->baseAmount, 4) < 0){
    		$row->baseAmount = "<span style='color:red'>{$row->baseAmount}</span>";
    	}
    	if(round($rec->baseQuantity, 4) < 0){
    		$row->baseQuantity = "<span style='color:red'>{$row->baseQuantity}</span>";
    	}
    	 
    	// Нулевия ред е винаги началното салдо
    	$zeroRec = array('docId'      => "Начален баланс",
    			'valior'	  => $data->fromDate,
    			'blAmount'   => $rec->baseAmount,
    			'blQuantity' => $rec->baseQuantity,
    			'ROW_ATTR'   => array('style' => 'background-color:#eee;font-weight:bold'));
    	 
    	// Нулевия ред е винаги началното салдо
    	$lastRec = array('docId'      => "Краен баланс",
    			'valior'	  => $data->toDate,
    			'blAmount'   => $rec->blAmount,
    			'blQuantity' => $rec->blQuantity,
    			'ROW_ATTR'   => array('style' => 'background-color:#eee;font-weight:bold'));
    	 
    	$data->recs = $this->Balance->recs;
    	unset($this->Balance->recs);
    	 
    	// Добавяне на началното и крайното салдо към цялата история
    	(count($this->history)) ? array_unshift($this->Balance->history, $zeroRec) : $this->Balance->history[] = $zeroRec;
    	$this->Balance->history[] = $lastRec;
    	
    	if($data->pager->page == 1){
    		// Добавяне на нулевия ред към историята
    		if(count($data->recs)){
    			array_unshift($data->recs, $lastRec);
    		} else {
    			$data->recs = array($lastRec);
    		}
    	}
    	 
    	// Ако сме на единствената страница или последната, показваме началното салдо
    	if($data->pager->page == $data->pager->pagesCount || $data->pager->pagesCount == 0){
    		$data->recs[] = $zeroRec;
    	}
    	 
    	// Подготвя средното салдо
    	$this->prepareMiddleBalance($data);
    	 
    	// За всеки запис, обръщаме го във вербален вид
    	if(count($data->recs)){
    		foreach ($data->recs as $jRec){
    			$data->rows[] = $this->getVerbalHistoryRow($jRec, $Double, $Date);
    		}
    	}
    }
    
    
    /**
     * Подготовка на вербалното представяне на един ред от историята
     */
    private function getVerbalHistoryRow($rec, $Double, $Date)
    {
    	$arr['valior'] = $Date->toVerbal($rec['valior']);
    	 
    	// Ако има отрицателна сума показва се в червено
    	foreach (array('debitAmount', 'debitQuantity', 'creditAmount', 'creditQuantity', 'blQuantity', 'blAmount') as $fld){
    
    		$arr[$fld] = $Double->toVerbal($rec[$fld]);
    		if(round($rec[$fld], 6) < 0){
    			$arr[$fld] = "<span style='color:red'>{$arr[$fld]}</span>";
    		}
    	}
    
    	try{
    		if(cls::load($rec['docType'], TRUE)){
    			$Class = cls::get($rec['docType']);
    			$arr['docId'] = $Class->getLink($rec['docId']);
    			$arr['reason'] = $Class->getContoReason($rec['docId']);
    		}
    	} catch(Exception $e){
    		if(is_numeric($rec['docId'])){
    			$arr['docId'] = "<span style='color:red'>" . tr("Проблем при показването") . "</span>";
    		} else {
    			$arr['docId'] = $rec['docId'];
    		}
    	}
    	 
    	if($rec['ROW_ATTR']){
    		$arr['ROW_ATTR'] = $rec['ROW_ATTR'];
    	}
    	 
    	return (object)$arr;
    }
    
    
    /**
     * Изчислява средното салдо
     */
    private function prepareMiddleBalance(&$data)
    {
    	$recs = $this->Balance->history;
    	
    	$tmpArray = array();
    	$quantity = $amount = 0;
    	 
    	// Създаваме масив с ключ валйора на документа, така имаме списък с
    	// последните записи за всяка дата
    	if(count($recs)){
    		foreach ($recs as $rec){
    			$tmpArray[$rec['valior']] = $rec;
    		}
    	}
    	 
    	// Нулираме му ключовете за по-лесно обхождане
    	$tmpArray = array_values($tmpArray);
    	if(count($tmpArray)){
    
    		// За всеки запис
    		foreach ($tmpArray as $id => $arr){
    			 
    			// Ако не е последния елемент
    			if($id != count($tmpArray)-1){
    
    				// Взимаме дните между следващата дата и текущата от записа
    				$value = dt::daysBetween($tmpArray[$id+1]['valior'], $arr['valior']);
    			} else {
    
    				// Ако сме на последната дата
    				$value = 1;
    			}
    			 
    			// Умножяваме съответните количества по дните разлика
    			$quantity += $value * $arr['blQuantity'];
    			$amount += $value * $arr['blAmount'];
    		}
    	}
    	 
    	// Колко са дните в избрания период
    	$daysInPeriod = dt::daysBetween($data->toDate, $data->fromDate) + 1;
    	 
    	// Средното салдо е събраната сума върху дните в периода
    	@$data->rec->midQuantity = $quantity / $daysInPeriod;
    	@$data->rec->midAmount = $amount / $daysInPeriod;
    	 
    	// Вербално представяне на средното салдо
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	$data->row->midQuantity = $Double->toVerbal($data->rec->midQuantity);
    	$data->row->midAmount = $Double->toVerbal($data->rec->midAmount);
    }
    
    
    /**
     * Рендиране на историята
     *
     * @param stdClass $data
     * @return core_ET $tpl
     */
    private function renderHistory(&$data)
    {
    	// Взимаме шаблона за историята
    	$tpl = getTplFromFile('acc/tpl/SingleLayoutBalanceHistory.shtml');
    	 
    	if($data->toolbar){
    		$tpl->append($data->toolbar->renderHtml(), 'HystoryToolbar');
    	}
    	 
    	// Проверка дали всички к-ва равнят на сумите
    	$equalBl = TRUE;
    	if(count($data->rows)){
    		foreach ($data->rows as $row){
    			if(trim($row->blQuantity) != trim($row->blAmount)){
    				$equalBl = FALSE;
    			}
    		}
    	}
    	 
    	// Подготвяме таблицата с данните извлечени от журнала
    	$table = cls::get('core_TableView', array('mvc' => $this->Balance));
    	$data->listFields = array('valior'         => 'Вальор',
    							  'docId'  		   => 'Документ',
					    		  'reason'		   => 'Забележки',
					    		  'debitQuantity'  => 'Дебит->К-во',
					    		  'debitAmount'    => 'Дебит->Сума',
					    		  'creditQuantity' => 'Кредит->К-во',
					    		  'creditAmount'   => 'Кредит->Сума',
					    		  'blQuantity'     => 'Остатък->К-во',
					    		  'blAmount'       => 'Остатък->Сума',
    	);
    
    	// Ако равнят не показваме количествата
    	if($equalBl){
    		unset($data->listFields['debitQuantity'], $data->listFields['creditQuantity'], $data->listFields['blQuantity']);
    	}
    
    	// Ако сумите на крайното салдо са отрицателни - оцветяваме ги
    	$details = $table->get($data->rows, $data->listFields);
    
    	foreach (array('blQuantity', 'blAmount', 'midQuantity', 'midAmount') as $fld){
    		if($data->rec->$fld < 0){
    			$data->row->$fld = "<span style='color:red'>{$data->row->$fld}</span>";
    		}
    	}
    
    	$tpl->placeObject($data->row);
    
    	// Добавяне в края на таблицата, данните от журнала
    	$tpl->replace($details, 'DETAILS');
    
    	// Рендиране на филтъра
    	$tpl->append($this->renderListFilter($data), 'listFilter');
    
    	// Рендиране на пейджъра
    	if($data->pager){
    		$tpl->append($this->renderListPager($data));
    	}
    
    	// Връщаме шаблона
    	return $tpl;
    }
}