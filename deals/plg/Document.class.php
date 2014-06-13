<?php


/**
 * Клас 'deals_plg_Document'
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_plg_Document extends core_Plugin
{
	
	
	/**
	 *  Обработки по вербалното представяне на данните
	 */
	static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		$row->number = $mvc->getHandle($rec->id);
		if($fields['-list']){
			$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
		}
		 
		if($fields['-single']){
			if(deals_Deals::haveRightFor('single', $rec->dealId)){
				$row->dealId = ht::createLink($row->dealId, array('deals_Deals', 'single', $rec->dealId));
			}
	
			// Показваме заглавието само ако не сме в режим принтиране
			if(!Mode::is('printing')){
				$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>#{$mvc->abbr}{$row->id}</b>" . " ({$row->state})" ;
			}
	
			$baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
	
			if($baseCurrencyId != $rec->currencyId) {
				$Double = cls::get('type_Double');
				$Double->params['decimals'] = 2;
				$rec->amountBase = round($rec->amount * $rec->rate, 2);
				$row->amountBase = $Double->toVerbal($rec->amountBase);
				$row->baseCurrency = currency_Currencies::getCodeById($baseCurrencyId);
			} else {
				unset($row->rate);
			}
		}
	}
	
	
	/**
	 * Извиква се след подготовката на toolbar-а за табличния изглед
	 */
	static function on_AfterPrepareListToolbar($mvc, &$data)
	{
		if(!empty($data->toolbar->buttons['btnAdd'])){
			$data->toolbar->removeBtn('btnAdd');
		}
	}
	
	
	/**
	 * Проверка дали нов документ може да бъде добавен в
	 * посочената папка като начало на нишка
	 *
	 * @param $folderId int ид на папката
	 */
	public function on_AfterCanAddToFolder($mvc, &$res, $folderId)
	{
		return $res = FALSE;
	}
	
	
	/**
	 * Извиква се след изчисляването на необходимите роли за това действие
	 */
	function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'restore' && isset($rec)){
			$dealState = deals_Deals::fetchField($rec->dealId, 'state');
			if($dealState != 'active'){
				$res = 'no_one';
			}
		}
		
		// Документа не може да се контира, ако ориджина му е в състояние 'closed'
		if($action == 'conto' && isset($rec)){
			$origin = $mvc->getOrigin($rec);
			if($origin && $origin->haveInterface('bgerp_DealAggregatorIntf')){
				$originState = $origin->fetchField('state');
				if($originState === 'closed'){
					$res = 'no_one';
				}
			}
		}
	}
	
	
	/**
	 * Извиква се след оттегляне на сделка, оттегля всички документи, които са я прехванали
	 */
	function on_AfterRejectAll($mvc, &$res, $dealId)
	{
		$query = $mvc->getQuery();
		$query->where("#dealId = {$dealId}");
		$query->where("#state != 'rejected'");
		$count = $query->count();
		
		while($rec = $query->fetch()){
			
			try{
				$mvc->reject($rec->id);
			} catch(Exception $e){
				$mvc->log("Проблем с оттеглянето на {$mvc->singleTitle}, {$rec->id}");
			}
		}
		
		if($count){
			core_Statuses::newStatus(tr("|Оттеглени са|* {$count} {$mvc->title}"));
		}
	}
	
	
	/**
	 * Извиква се след възстановяването на сделка, оттегля всички документи, които са я прехванали
	 */
	function on_AfterRestoreAll($mvc, &$res, $dealId)
	{
		$query = $mvc->getQuery();
		$query->where("#dealId = {$dealId}");
		$query->where("#state = 'rejected'");
		
		$count = $query->count();
		while($rec = $query->fetch()){
			try{
				$mvc->restore($rec->id);
			} catch(Exception $e){
				$mvc->log("Проблем с възстановяване на {$mvc->singleTitle}, {$rec->id}");
			}
		}
		
		if($count){
			core_Statuses::newStatus(tr("|Възстановени са|* {$count} {$mvc->title}"));
		}
	}
	
	
	/**
	 * Нотифицира пораждащия документ че е създадено ново прихващане, нотифицира също и другата замесена
	 * сделка че има документ който я засяга
	 */
	public static function on_AfterNotificateOrigin($mvc, &$res, $id)
	{
		$rec = $mvc->fetchRec($id);
		
		// Нотифицираме origin-документа, че някой от веригата му се е променил
		if ($origin = $mvc->getOrigin($rec)) {
			$ref = new core_ObjectReference($mvc, $rec);
			$origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
		
			$assocDeal = new core_ObjectReference('deals_Deals', $rec->dealId);
			$assocDeal->getInstance()->invoke('DescendantChanged', array($assocDeal, $ref));
		}
	}
}