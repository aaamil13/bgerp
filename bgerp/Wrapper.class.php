<?php



/**
 * Клас 'bgerp_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'bgerp'
 *
 *
 * @category  all
 * @package   bgerp
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class bgerp_Wrapper extends core_Plugin
{
    
    
    /**
     * Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('bgerp_Menu', 'Меню');
        $tabs->TAB('bgerp_Portal', 'Портал');
        $tabs->TAB('bgerp_Notifications', 'Известия');
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » " , 'PAGE_TITLE');
    }
}