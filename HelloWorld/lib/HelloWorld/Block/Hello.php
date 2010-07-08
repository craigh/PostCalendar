<?php
/**
 * Copyright Craig Heydenburg 2010 - HelloWorld
 *
 * HelloWorld
 * Demonstration of Zikula Module
 *
 * @license GNU/LGPLv3 (or at your option, any later version).
 */

class HelloWorld_Block_Hello extends Zikula_Block
{
    /**
     * initialise block
     */
    public function init()
    {
        SecurityUtil::registerPermissionSchema('HelloWorld:helloblock:', 'Block title::');
    }
    
    /**
     * get information on block
     */
    public function info()
    {
        return array(
            'text_type'        => 'hello',
            'module'           => 'HelloWorld',
            'text_type_long'   => $this->__('Sample HelloWorld Block'),
            'allow_multiple'   => true,
            'form_content'     => false,
            'form_refresh'     => false,
            'show_preview'     => true,
            'admin_tableless'  => true);
    }
    
    /**
     * display block
     */
    public function display($blockinfo)
    {
        if (!SecurityUtil::checkPermission('HelloWorld:helloblock:', "$blockinfo[title]::", ACCESS_OVERVIEW)) {
            return;
        }
        if (!ModUtil::available('HelloWorld')) {
            return;
        }
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        $this->view->assign('variable', $var);
    
        $blockinfo['content'] = $this->view->fetch('blocks/hello.tpl');
    
        return BlockUtil::themeBlock($blockinfo);
    }
    
    /**
     * modify block settings ..
     */
    public function modify($blockinfo)
    {
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        $this->view->assign('vars', $vars);
    
        return $this->view->fetch('blocks/hello_modify.tpl');
    }
    
    /**
     * update block settings
     */
    public function update($blockinfo)
    {
        $vars = BlockUtil::varsFromContent($blockinfo['content']);
    
        // alter the corresponding variable
        $vars['eid']           = FormUtil::getPassedValue('eid', '', 'POST');
    
        // write back the new contents
        $blockinfo['content'] = BlockUtil::varsToContent($vars);
    
        // clear the block cache
        Zikula_View::clear_cache('blocks/hello.tpl');
    
        return $blockinfo;
    }
} // end class def