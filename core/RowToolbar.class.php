<?php



/**
 * Клас 'core_RowToolbar' - Dropdown toolbar за листовия изглед
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_RowToolbar extends core_BaseClass
{
    
    
    /**
     * Масив с връзките
     */
    var $links = array();
    
    
    /**
     * Добавя бутон, който прехвърля към хипервръзка
     * 
     * @param string $title
     * @param mixed $url
     * @param string|array $params
     * @param array $moreParams
     */
    function addLink($title, $url, $params = array(), $moreParams = array())
    {
        $btn = new stdClass();
        $btn->url = $url;
        $btn->title = $title;
        $this->add($btn, $params, $moreParams);
    }
    
     
    /**
     * Добавя бутон, който задейства js функция
     */
    function addFnLink($title, $function, $params = array(), $moreParams = array())
    {
        $btn = new stdClass();
    	$btn->type = 'function';
        $btn->title = $title;
        $btn->fn = $function;
        $this->add($btn, $params, $moreParams);
    }
    
    
    /**
     * Добавя описание на бутон във вътрешния масив
     */
    function add(&$btn, &$params, &$moreParams)
    {
        $params = arr::combine($params, $moreParams);
        
        if($params['target']) {
            $btn->newWindow = $params['target'];
            unset($params['target']);
        }
        
        if($params['warning']) {
            $btn->warning = $params['warning'];
            unset($params['warning']);
        }
        
    	if($params['error']) {
            $btn->error = $params['error'];
            unset($params['error']);
        }
        
        if($params['order']) {
            $btn->order = $params['order'];
            unset($params['order']);
        } elseif($btn->error){
        	$btn->order = 40;
        } elseif($btn->warning) {
            $btn->order = 30;
        } elseif($btn->newWindow) {
            $btn->order = 20;
        } else {
            $btn->order = 10;
        }
        
        $btn->order += count($this->links) / 10000;
        
        $btn->attr = $params;
        
        $id = $params['id'] ? $params['id'] : $btn->title;
        
        $this->links[$id] = $btn;
    }
    
    
    /**
     * Преименува заглавието на бутона
     * 
     * @param string $id - ид на бутона
     * @param string $name - новото му име
     * @return void
     */
    function renameLink($id, $name)
    {
    	expect($this->links[$id]);
    	$this->links[$id]->title = $name;
    }
    
    
    /**
     * Премахва посочения бутон/бутони в полето $ids
     * Запазва бутоните посочени в $remains 
     */
    function removeBtn($ids, $remains = NULL)
    {
        $ids = arr::make($ids, TRUE);
        $remains = arr::make($remains, TRUE);
        foreach($this->links as $id => $btn) { 
            if(($ids['*'] || $ids[$id]) && !$remains[$id]) {
                unset($this->links[$id]); 
                $cnt++;
            }
        }

        return $cnt;
    }
    
    
    /**
     * Добавя атрибут 'warning' на избраните бутони
     * 
     * @param mixed $ids - масив с ид-та на бутони
     * @param string $error - съобщение за грешка
     */
    function setWarning($ids, $warning)
    {
    	$ids = arr::make($ids, TRUE);
    	expect(count($ids));
    	
    	$buttons = (isset($ids['*'])) ? $this->links : $ids;
    	foreach($buttons as $id => $btn){
    		expect($this->links[$id]);
    	 	$this->links[$id]->warning = $warning;
    	}
    }
    
    
    /**
     * Добавя атрибут 'error' на избраните бутони
     * 
     * @param mixed $ids - масив с ид-та на бутони
     * @param string $error - съобщение за грешка
     */
	function setError($ids, $error)
    {
    	$ids = arr::make($ids, TRUE);
    	expect(count($ids));
    	
    	$buttons = (isset($ids['*'])) ? $this->links : $ids;
    	foreach($buttons as $id => $btn){
    		expect($this->links[$id]);
    	 	$this->links[$id]->error = $error;
    	}
    }
    

    /**
     * Връща броя на бутоните на тулбара
     */
    public function count()
    {
        return count($this->links);
    }
    
    
    /**
     * Връща html - съдържанието на лентата с инструменти
     */
    function renderHtml_()
    {

        $dropDownIcon = sbf("img/16/arrow_down3.png", '');
        $layout = new ET("\n" . 
                        "<div class='modal-toolbar rowtoolsGroup'>[#ROW_LINKS#]</div>" .
                        "<img class='more-btn button' src='{$dropDownIcon}'>");
        
        if (!count($this->links) > 0) return;
        
        if (Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('text', 'plain')) return;
        
        // Сортираме бутоните
        arr::order($this->links);            
        
        foreach($this->links as $id => $linkObj) {
            $attr = arr::combine($linkObj->attr, array('id' => $this->id));
            ht::setUniqId($attr);
            $link = ht::createLink($linkObj->title, $linkObj->url, $linkObj->error ? $linkObj->error : $linkObj->warning, $attr); 
            $layout->append($link, 'ROW_LINKS');
        }
        
        return $layout;
    }
    
    
    
    /**
     * Проверява дали даден бутон го има в тулбара
     * 
     * @param int $id - ид на бутон
     * @return boolean TRUE/FALSE - имали го бутона или не
     */
    public function hasBtn($id)
    {
    	return isset($this->links[$id]);
    }
}