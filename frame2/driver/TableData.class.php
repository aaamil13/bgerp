<?php



/**
 * Базов драйвер за справки показващи стандартни таблични данни
 *
 *
 * @category  bgerp
 * @package   frame2
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class frame2_driver_TableData extends frame2_driver_Proto
{
	
	
	/**
	 * Брой записи на страница
	 *
	 * @var int
	 */
	protected $listItemsPerPage = 30;
	
	
	/**
	 * Полета от таблицата за скриване, ако са празни
	 *
	 * @var int
	 */
	protected $filterEmptyListFields;
	
	
	/**
	 * Полета за хеширане на таговете
	 * 
	 * @see uiext_Labels
	 * @var varchar
	 */
	protected $hashField;
	
	
	/**
	 * Кое поле от $data->recs да се следи, ако има нов във новата версия
	 *
	 * @var varchar
	 */
	protected $newFieldToCheck;
	
	
	/**
	 * По-кое поле да се групират листовите данни
	 */
	protected $groupByField;
	
	
	/**
	 * Дали групиращото поле да е на отделен ред или не
	 */
	protected $groupedFieldOnNewRow = TRUE;
	
	
	/**
	 * Връща заглавието на отчета
	 *
	 * @param stdClass $rec - запис
	 * @return string|NULL  - заглавието или NULL, ако няма
	 */
	public function getTitle($rec)
	{
		$title = core_Classes::fetchField("#id = {$this->getClassId()}", 'title');
		$title = explode(" » ", $title);
		$title = (count($title) == 2) ? $title[1] : $title[0];
		
		return $title;
	}
	
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 *
	 * @param stdClass $rec        - запис на справката
	 * @return stdClass|NULL $data - подготвените данни
	 */
	public function prepareData($rec)
	{
		$data = new stdClass();
		$data->recs = $this->prepareRecs($rec, $data);
		setIfNot($data->groupByField, $this->groupByField);
		setIfNot($data->groupedFieldOnNewRow, $this->groupedFieldOnNewRow);
		
		return $data;
	}
	
	
	/**
	 * Рендиране на данните на справката
	 *
	 * @param stdClass $rec - запис на справката
	 * @return core_ET      - рендирания шаблон
	 */
	public function renderData($rec)
	{
		$tpl = new core_ET("[#PAGER_TOP#][#TABLE#][#PAGER_BOTTOM#]");
	
		$data = (is_object($rec->data)) ? $rec->data : new stdClass();
		$data->listFields = $this->getListFields($rec);
		$data->rows = array();
	
		// Подготовка на пейджъра
		if(!Mode::isReadOnly()){
			setIfNot($itemsPerPage, $rec->listItemsPerPage, $this->listItemsPerPage);
			$data->Pager = cls::get('core_Pager',  array('itemsPerPage' => $itemsPerPage));
			$data->Pager->setPageVar('frame2_Reports', $rec->id);
			$data->Pager->itemsCount = count($data->recs);
		}
	
		// Вербализиране само на нужните записи
		if(is_array($data->recs)){
			
			// Ако има поле за групиране, предварително се групират записите
			if(isset($data->groupByField)){
				$data->recs = $this->orderByGroupField($data->recs, $data->groupByField);
			}
			
			foreach ($data->recs as $index => $dRec){
				if(isset($data->Pager) && !$data->Pager->isOnPage()) continue;
				$data->rows[$index] = $this->detailRecToVerbal($rec, $dRec);
			}
		}
		
		// Рендиране на пейджъра
		if(isset($data->Pager)){
			$tpl->replace($data->Pager->getHtml(), 'PAGER_TOP');
			$tpl->replace($data->Pager->getHtml(), 'PAGER_BOTTOM');
		}
	
		// Рендиране на лист таблицата
		$fld = $this->getTableFieldSet($rec);
		$table = cls::get('core_TableView', array('mvc' => $fld));
		
		// Показване на тагове
		if(core_Packs::isInstalled('uiext')){
			uiext_Labels::showLabels($this, $rec->containerId, $data->recs, $data->rows, $data->listFields, $this->hashField, 'Таг', $tpl, $fld);
		}

		$filterFields = arr::make($this->filterEmptyListFields, TRUE);
		$filterFields['_tagField'] = '_tagField';
		
		if(isset($data->groupByField)){
			$found = FALSE;
			
			// Групиране само ако има поне една стойност за групиране
			$groupByField = $data->groupByField;
			array_walk($data->recs, function ($r) use ($groupByField, &$found) {if(isset($r->{$groupByField})) {$found = TRUE;}});
			
			if($found === TRUE){
				$this->groupRows($data->recs, $data->rows, $data->listFields, $data->groupByField, $data);
				$filterFields[$data->groupByField] = $data->groupByField;
			}
		}
		
		$data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, implode(',', $filterFields));
	
		$tpl->append($table->get($data->rows, $data->listFields), 'TABLE');
		$tpl->removeBlocks();
		$tpl->removePlaces();
		
		// Връщане на шаблона
		return $tpl;
	}
	
	
	/**
	 * Подреждане на записите първо по-поле и после групиране по полр
	 *
	 * @param int $recs
	 * @param string $field
	 * @return array $newRecs
	 */
	private function orderByGroupField($recs, $groupField)
	{
		$newRecs = array();
		foreach ($recs as $i => $r){
			$newRecs[$i] = $r;
			$subArr = array_filter($recs, function ($a) use ($r, $groupField){return ($a->{$groupField} == $r->{$groupField});});
			if(count($subArr)){
				$newRecs = array_replace($newRecs, $subArr);
			}
		}
	
		return $newRecs;
	}
	
	
	/**
	 * След рендиране на единичния изглед
	 *
	 * @param cat_ProductDriver $Driver
	 * @param embed_Manager $Embedder
	 * @param core_ET $tpl
	 * @param stdClass $data
	 */
	protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
	{
		uiext_Labels::enable($tpl);
	}
	
	
	/**
	 * Групира записите по поле
	 * 
	 * @param array $recs
	 * @param array $rows
	 * @param array $listFields
	 */
	protected function groupRows($recs, &$rows, $listFields, $field, $data)
	{
		if(!count($rows)) return;
		$columns = count($listFields);
		
		$groups = array();
		foreach ($rows as $index => $row){
			$groups[$recs[$index]->{$field}] = $row->{$field};
		}
		
		$newRows = $rowAttr = array();
		$rowAttr['class'] = ' group-by-field-row';
		foreach ($groups as $groupId => $groupVerbal){
			if($data->groupedFieldOnNewRow === TRUE){
				$groupVerbal = ($groupVerbal instanceof core_ET) ? $groupVerbal->getContent() : $groupVerbal;
				$groupVerbal = $this->getGroupedTr($columns, $groupId, $groupVerbal, $data);
					
				$newRows['|' . $groupId] = ht::createElement('tr', $rowAttr, $groupVerbal);
				$newRows['|' . $groupId]->removeBlocks();
				$newRows['|' . $groupId]->removePlaces();
			}
			
			$firstRow = TRUE;
			
			// За всички записи
			foreach ($rows as $index => $row1){
				$r = $recs[$index];
				if($r->{$field} == $groupId){
					if($data->groupedFieldOnNewRow === TRUE || ($data->groupedFieldOnNewRow === FALSE && $firstRow !== TRUE)){
						unset($rows[$index]->{$field});
					}
					
					if(is_object($rows[$index])){
						$newRows[$index] = clone $rows[$index];
							
						// Веднъж групирано, премахваме записа от старите записи
						unset($rows[$index]);
					}
					
					$firstRow = FALSE;
				}
			}
		}
		
		$rows = $newRows;
	}
	
	
	/**
	 * Подготовка на реда за групиране
	 *
	 * @param int $columnsCount   - брой колони
	 * @param string $groupValue  - невербалното име на групата
	 * @param string $groupVerbal - вербалното име на групата
	 * @param stdClass $data      - датата
	 * @return string             - съдържанието на групиращия ред
	 */
	protected function getGroupedTr($columnsCount, $groupValue, $groupVerbal, &$data)
	{
		$groupVerbal = "<td style='padding-top:9px;padding-left:5px;' colspan='{$columnsCount}'><b>" . $groupVerbal . "</b></td>";
	
		return $groupVerbal;
	}
	
	
	/**
	 * Връща полетата за експортиране във csv
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportFieldset($rec)
	{
		$fld = $this->getTableFieldSet($rec, TRUE);
	
		return $fld;
	}
	
	
	/**
	 * Подготвя данните на справката от нулата, които се записват в модела
	 *
	 * @param stdClass $rec        - запис на справката
	 * @param boolean $export    - таблицата за експорт ли е
	 * @return stdClass|NULL $data - подготвените данни
	 */
	protected function getListFields($rec, $export = FALSE)
	{
		$listFields = array();
		
		$fieldset = $this->getTableFieldSet($rec, $export);
		$fields = $fieldset->selectFields();
		if(is_array($fields)){
			foreach ($fields as $name => $fld){
				$listFields[$name] = $fld->caption;
			}
		}
		
		return $listFields;
	}
	
	
	/**
	 * Връща редовете на CSV файл-а
	 *
	 * @param stdClass $rec
	 * @return array
	 */
	public function getCsvExportRecs($rec)
	{
		$recs = array();
		
		if(is_array($rec->data->recs)){
			foreach ($rec->data->recs as $dRec){
				$recs[] = $this->getCsvRec($rec, $dRec);
			}
		}
		
		return $recs;
	}
	
	
	/**
	 * Подготовка на реда за експорт във CSV
	 * 
	 * @param stdClass $rec
	 * @param stdClass $dRec
	 * @return stdClass
	 */
	public function getCsvRec_($rec, $dRec)
	{
		return $dRec;
	}
	
	
	/**
	 * Да се изпраща ли нова нотификация на споделените потребители, при опресняване на отчета
	 *
	 * @param stdClass $rec
	 * @return boolean $res
	 */
	public function canSendNotificationOnRefresh($rec)
	{
		// Намира се последните две версии
		$query = frame2_ReportVersions::getQuery();
		$query->where("#reportId = {$rec->id}");
		$query->orderBy('id', 'DESC');
		$query->limit(2);
	
		// Маха се последната
		$all = $query->fetchAll();
		unset($all[key($all)]);
	
		// Ако няма предпоследна, бие се нотификация
		if(!count($all)) return TRUE;
		
		if(empty($this->newFieldToCheck)) return FALSE;
		
		$oldRec = $all[key($all)]->oldRec;
		$dataRecsNew = $rec->data->recs;
		$dataRecsOld = $oldRec->data->recs;
	
		$newContainerIds = $oldContainerIds = array();
		
		if(is_array($rec->data->recs)){
			$newContainerIds = arr::extractValuesFromArray($rec->data->recs, $this->newFieldToCheck);
		}
	
		if(is_array($oldRec->data->recs)){
			$oldContainerIds = arr::extractValuesFromArray($oldRec->data->recs, $this->newFieldToCheck);
		}
	
		// Ако има нови документи бие се нотификация
		$diff = array_diff_key($newContainerIds, $oldContainerIds);
		$res = (is_array($diff) && count($diff));
	
		return $res;
	}
	
	
	/**
	 * След добавени
	 *
	 * @param frame2_driver_Proto $Driver - драйвер
	 * @param embed_Manager $Embedder     - ембедър
	 * @param core_Fieldset $fieldset     - форма
	 */
	protected static function on_AfterAddFields(frame2_driver_Proto $Driver, embed_Manager $Embedder, core_Fieldset &$fieldset)
	{
		$fieldset->FLD('listItemsPerPage', 'int(min=10,Max=100)', "caption=Други настройки->Елементи на страница,after=changeFields,autohide,placeholder={$Driver->listItemsPerPage}");
	}
	
	
	/**
	 * Кои записи ще се показват в таблицата
	 * 
	 * @param stdClass $rec
	 * @param stdClass $data
	 * @return array
	 */
	protected abstract function prepareRecs($rec, &$data = NULL);
	
	
	/**
	 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
	 *
	 * @param stdClass $rec  - записа
	 * @param stdClass $dRec - чистия запис
	 * @return stdClass $row - вербалния запис
	 */
	protected abstract function detailRecToVerbal($rec, &$dRec);
	
	
	/**
	 * Връща фийлдсета на таблицата, която ще се рендира
	 *
	 * @param stdClass $rec   - записа
	 * @param boolean $export - таблицата за експорт ли е
	 * @return core_FieldSet  - полетата
	 */
	protected abstract function getTableFieldSet($rec, $export = FALSE);
}
