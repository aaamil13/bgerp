<?php



/**
 * Ценови групи
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Групи
 */
class price_Groups extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Ценови групи';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, price_Wrapper';
   
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'id, title, description, productsCount=Продукти';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
        
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'user';
    

    /**
     * Поле за връзка към единичния изглед
     */
    var $rowToolsSingleField = 'title';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'price/tpl/SingleLayoutGroups.shtml';
    
    var $details = 'ProductInGroup=price_GroupOfProducts';
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Група');
        $this->FLD('description', 'text', 'caption=Описание');
		
        $this->setDbUnique('title');
    }
    

    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'delete') {
            if($rec->id && price_GroupOfProducts::fetch("#groupId = {$rec->id}")) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    static function on_AfterPrepareSingleTitle($mvc, &$data)
    { 
    	$title = $mvc->getVerbal($data->rec, 'title');
    	$data->title = "|*" . $title;
    	
    }
    
    
    /**
     * Малко манипулации след подготвянето на формата за филтриране
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec, $fields = array())
    {
        $int = cls::get('type_Int');
    	$row->productsCount = $int->toVerbal($mvc->countProductsInGroup($rec->id));
    }
    
    
	/**
     * Преброява броя на продуктите в групата
     * @param int $id - ид на група
     * @return int - броя уникални продукти в група
     */
    public function countProductsInGroup($id)
    {
    	$i = 0;
    	$query = price_GroupOfProducts::getQuery();
    	$query->orderBy('#validFrom', 'DESC');
       	$used = array();
         while($rec = $query->fetch()) {
         	if($used[$rec->productId]) continue;
            if($id == $rec->groupId) {
            	$i++;
            }
            $used[$rec->productId] = TRUE;
         }
       
         return $i;
    }
}