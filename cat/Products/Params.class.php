<?php

class cat_Products_Params extends core_Detail
{
	var $masterKey = 'productId';
	
	var $title = 'Параметри';
	
	var $listFields = 'paramId, paramValue';
	
	var $loadList = 'cat_Wrapper';
	
    /**
     *  Активния таб в случай, че wrapper-а е таб контрол.
     */
    var $tabName = 'cat_Products';
	
    function description()
	{
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'input=hidden');
		$this->FLD('paramId', 'key(mvc=cat_Params,select=name)', 'input,caption=Параметър');
		$this->FLD('paramValue', 'varchar(255)', 'input,caption=Стойност');

		$this->setDbUnique('productId,paramId');
	}
	
	function on_AfterPrepareListToolbar($mvc, $data)
	{
		$data->toolbar->removeBtn('*');
		$data->toolbar->addBtn('Промяна', array($this, 'edit', 'productId'=>$data->masterId));
	}
	
	function on_AfterPrepareListFields($mvc, $data)
	{
		$data->query->orderBy('#id');
	}
	
	
	function on_AfterPrepareListRecs($mvc, $data)
	{
		$recs = &$data->recs;
		$rows = &$data->rows;
		foreach ($recs as $i=>$rec) {
			$row = $rows[$i];
			$row->paramValue .= ' ' . cat_Params::fetchField($rec->paramId, 'suffix');
		}
	}
	
	
	function on_AfterPrepareEditForm($mvc, $data)
	{
		$productId = Request::get('productId', "key(mvc={$mvc->Master->className})");
		$data->form = $mvc->getParamsForm($productId);
	}
	
	function on_AfterPrepareEditToolbar($mvc, $data)
	{
		$productId = Request::get('productId', "key(mvc={$mvc->Master->className})");
		$data->form->toolbar->addBtn('Отказ', array('cat_Products', 'single', $productId), array('class'=>'btn-cancel'));
	}
	
	static function &getParamsForm($productId, &$form = NULL)
	{
		$productRec = cat_Products::fetch($productId);
		$form = cat_Categories::getParamsForm($productRec->categoryId, $form);
		
		if (!$form->getField('productId', FALSE)) {
			$form->FLD('productId', 'key(mvc=cat_Products)', 'silent,input=hidden,value='.$productId);
		}
		
		if (!$form->title) {
			$form->title = $productRec->name;
		}
		
		$query = static::getQuery();
		$query->where("#productId = {$productId}");
		
		while ($rec = $query->fetch()) {
			$form->setDefault("value_{$rec->paramId}", $rec->paramValue);
			$form->FLD("id_{$rec->paramId}", "key(mvc=cat_Products_Params)", "input=hidden,value={$rec->id}");
		}
		
		return $form;
	}
	
	static function processParamsForm($form)
	{
		$productId = $form->rec->productId;
		
		foreach ((array)$form->rec as $n=>$v) {
			list($n, $key) = explode('_', $n, 2);
			if ($n == 'value') {
				$paramId    = $key;
				$id         = $form->rec->{"id_{$paramId}"};
				$paramValue = $v;

				$rec = (object)compact('id', 'productId', 'paramId', 'paramValue');
				static::save($rec);
			}
			
		}
	}
	
	function on_AfterInputEditForm($mvc, $form)
	{
		if ($form->isSubmitted()) {
			$mvc->processParamsForm($form);
			redirect(array('cat_Products', 'single', $form->rec->productId));
		}
	}
}