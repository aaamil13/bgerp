<?php


/** Oрганизациони структурии
 * 
 * @category  vendors
 * @package   orgchart
 * @author    Nevena Georgieva <nevena.georgieva89@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class orgchart_Adapter 
{    
    
    
    /**
     * начертаване на организациони структури по даден двумерен масив
     * 
     * @param array $orgData - двумерен масив, от който вземаме данните за структурата
     * 
     * $orgData трябва да има следната структура:
     * $orgData = array(
	 *		  	'0' => array (
	 *   					'id' => 1,                   
	 *   					'title' => "Title",
	 *  					'parent_id' => 'NULL'
	 *   			)
	 * id - id на елемента
     * title - текста, които ще се показва за всеки елемент
     * parent_id - id на родителя
     */
    static function render_($orgData)
    {
    	static $orgChartCnt;
    	if(!$orgChartCnt) $orgChartCnt = 0;
    	$orgChartCnt++;
    	$idChart = 'orgChart' . $orgChartCnt;
    
    	$level = 'NULL';
    	$nestedLists = static::transformArrayToNestedLists($orgData, $level);
    	
        // Създаваме шаблона
        $tpl = new ET();
        
        // Генерираме необходимия маркъп за плъгина
        $tpl->append("<div class='organisation'>{$nestedLists}</div><div id='{$idChart}'></div>");

        jquery_Jquery::enable($tpl);
         
        $tpl->push('orgchart/lib/jquery.orgchart.css', 'CSS');
        $tpl->push('orgchart/lib/jquery.orgchart.js', 'JS');
            
        jquery_Jquery::run($tpl, "$('.organisation > ul').orgChart({container: $('#{$idChart}'), interactive: true});", TRUE);
      
        
        return $tpl;
    }
	
    /**
     * рекурсивна функция, която от дадения масив генерира хтмл за вложени списъци
     */
   	static function transformArrayToNestedLists($array, $level) {
    	$html = '' ;
    	foreach ( $array as $currentArr ) {
    		if ($currentArr['parent_id'] == $level ) {
    			$html = $html . "\n<li>" . $currentArr['title'] . static::transformArrayToNestedLists( $array, $currentArr['id'] ) . "</li>\n";
    		}
    	}
    	return ($html==''?'':"\n<ul>". $html . "</ul>\n");
    }	
}