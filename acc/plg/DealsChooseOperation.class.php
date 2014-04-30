<?php



/**
 * Плъгин за покупките и продажбите. Дава им възможност за избор на операция преди контиране.
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_plg_DealsChooseOperation extends core_Plugin
{
    
	
	/**
	 * Масив с вербалните имена при избора на контиращи операции за покупки/продажби
	 */
    private static $contoMap = array(
    	'sales'    => array('pay'     => 'Прието плащане в брой в каса ',
    				'ship'    => 'Експедиране на продукти от склад ', 
    				'service' => 'Изпълнение на услуги'),
    
    	'purchase' => array('pay'     => 'Направено плащане в брой от каса ',
    				'ship'    => 'Вкарване на продукти в склад ',
    				'service' => 'Приемане на услуги')
    );
    
    
    /**
     * Извиква се след описанието на модела
     * 
     * @param core_Mvc $mvc
     */
    function on_AfterDescription(core_Mvc $mvc)
    {
    	// Може да се добавя само към покупка или продажба
    	expect($mvc instanceof sales_Sales || $mvc instanceof purchase_Purchases);
    	
    	if(empty($mvc->fields['contoActions'])){
    		$mvc->FLD('contoActions', 'set(activate,pay,ship)', 'input=none,notNull,default=activate');
    	}
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	$rec = &$data->rec;
	    
    	// Ако има опции за избор на контирането, подмяна на бутона за контиране
	    if(isset($data->toolbar->buttons['btnConto'])){
	    	$options = $mvc->getContoOptions($rec->id);
	    	if(count($options)){
	    		$data->toolbar->removeBtn('btnConto');
	    		
		    	// Проверка на счетоводния период, ако има грешка я показваме
	        	if(!acc_plg_Contable::checkPeriod($rec->valior, &$error)){
	        		$error = ",error={$error}";
	        	}
	        	
	    		$data->toolbar->addBtn('Активиране', array($mvc, 'chooseAction', $rec->id), "id=btnConto{$error}", 'ef_icon = img/16/tick-circle-frame.png,title=Активиране на документа');
	    	}
	    }
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     * 
     * @param core_Manager $mvc
     * @param core_ET $res
     * @param string $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$tpl, $action)
    {   
    	if(strtolower($action) == 'chooseaction'){
    		$id = Request::get('id', 'int');
	    	expect($rec = $mvc->fetch($id));
	    	expect($rec->state == 'draft');
	    	expect(acc_plg_Contable::checkPeriod($rec->valior, &$error), $error);
	    	$curStoreId = store_Stores::getCurrent('id', FALSE);
	    	$curCaseId  = cash_Cases::getCurrent('id', FALSE);
	    	
	    	// Трябва потребителя да може да контира
	    	$mvc->requireRightFor('conto', $rec);
	    	
	    	// Подготовка на формата за избор на опция
	    	$form = cls::get('core_Form');
	    	$form->title = "|Активиране на|* <b>" . $mvc->getTitleById($id). "</b>" . " ?";
	    	$form->info = '<b>Контиране на извършени на момента действия</b> (опционално):';
	    	
	    	// Извличане на позволените операции
	    	$options = $mvc->getContoOptions($rec);
	    	
	    	// Трябва да има избор на действие
	    	expect(count($options));
	    	
	    	// Подготовка на полето за избор на операция и инпут на формата
	    	$form->FNC('action', cls::get('type_Set', array('suggestions' => $options)), 'columns=1,input,caption=Изберете');
	    	
	    	$selected = array();
	    	
	    	// Ако има склад и експедиране и потребителя е логнат в склада, слагаме отметка
	    	if($options['ship'] && $rec->shipmentStoreId){
	    		if($rec->shipmentStoreId === $curStoreId){
	    			$selected[] = 'ship';
	    		}
	    	} elseif($options['ship']){
	    		$selected[] = 'ship';
	    	}
	    	
	    	// Ако има каса и потребителя е логнат в нея, Слагаме отметка
	    	if($options['pay'] && $rec->caseId){
	    		if($rec->caseId === $curCaseId){
	    			$selected[] = 'pay';
	    		}
	    	}
	    	
	    	$form->setDefault('action', implode(',', $selected));
	    	$form->input();
	    	
	    	// След като формата се изпрати
	    	if($form->isSubmitted()){
	    		
	    		// обновяване на записа с избраните операции
	    		$form->rec->action = 'activate' . (($form->rec->action) ? "," : "") . $form->rec->action;
	    		$rec->contoActions = $form->rec->action;
	    		$rec->isContable = ($form->rec->action == 'activate') ? 'activate' : 'yes';
	    		$mvc->save($rec);
	    		
	    		// Ако се експедира и има склад, форсира се логване
	    		if($options['ship'] && isset($rec->shipmentStoreId) && $rec->shipmentStoreId != $curStoreId){
	    			store_Stores::selectSilent($rec->shipmentStoreId);
	    		}
	    		
	    		// Ако има сметка и се експедира, форсира се логване
	    		if($options['pay'] && isset($rec->caseId) && $rec->caseId != $curCaseId){
	    			cash_Cases::selectSilent($rec->caseId);
	    		}
	    		
	    		// Контиране на документа
	    		$mvc->conto($id);
	    		
	    		// Редирект
	    		return redirect(array($mvc, 'single', $id));
	    	}
	    	
	    	$form->toolbar->addSbBtn('Активиране/Контиране', 'save', 'ef_icon = img/16/tick-circle-frame.png');
	        $form->toolbar->addBtn('Отказ', array($mvc, 'single', $id),  'ef_icon = img/16/close16.png');
	        
	        // Рендиране на формата
	    	$tpl = $mvc->renderWrapping($form->renderHtml());
	    	
	    	// ВАЖНО: спираме изпълнението на евентуални други плъгини
        	return FALSE;
    	}
    }
    
    
	/**
     * Какви операции ще се изпълнят с контирането на документа
     * @param int $id - ид на документа
     * @return array $options - опции
     */
    public function on_AfterGetContoOptions($mvc, &$res, $id)
    {
    	$options = array();
    	$rec = $mvc->fetchRec($id);
    	
    	// Заглавие за опциите, взависимост дали е покупка или продажба
    	$opt = ($mvc instanceof sales_Sales) ? self::$contoMap['sales'] : self::$contoMap['purchase'];
    	
    	// Имали складируеми продукти
    	$hasStorable = $mvc->hasStorableProducts($rec->id);
    	
    	// Ако има продукти за експедиране
    	if($hasStorable){
    		
    		// ... и има избран склад, и потребителя може да се логне в него
	    	if(isset($rec->shipmentStoreId) && store_Stores::haveRightFor('select', $rec->shipmentStoreId)){
	    		
	    		// Ако има очаквано авансово плащане, неможе да се експедира на момента
	    		if(cond_PaymentMethods::hasDownpayment($rec->paymentMethodId)){
	    			$hasDp = TRUE;
	    		}
	    		
	    		if(empty($hasDp)){
	    			
	    			// .. продуктите може да бъдат експедирани
	    			$storeName = store_Stores::getTitleById($rec->shipmentStoreId);
	    			$options['ship'] = "{$opt['ship']}\"{$storeName}\"";
	    		}
	    	}
    	} else {
    		
    		// ако има услуги те могат да бъдат изпълнени
    		if($mvc->hasStorableProducts($rec->id, FALSE)){
    			$options['ship'] = $opt['service'];
    		}
    	}
    	
    	// ако има каса, метода за плащане е COD и текущия потрбител може да се логне в касата
    	if(isset($rec->caseId) && cond_PaymentMethods::isCOD($rec->paymentMethodId) && cash_Cases::haveRightFor('select', $rec->caseId)){
    		
    		// може да се плати с продуктите
    		$caseName = cash_Cases::getTitleById($rec->caseId);
    		$options['pay'] = "{$opt['pay']} \"$caseName\"";
    	} 
    	
    	$res = $options;
    }
}
