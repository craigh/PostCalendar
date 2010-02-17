<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class postcalendar_contenttypesapi_postcaleventsPlugin extends contentTypeBase
{
    var $eid; // event id

    function getModule()
    {
        return 'postcalendar';
    }
    function getName()
    {
        return 'postcalevents';
    }
    function getTitle()
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        return __('Calendar Event List', $dom);
    }
    function getDescription()
    {
        $dom = ZLanguage::getModuleDomain('PostCalendar');
        return __('Displays a list of PostCalendar events.', $dom);
    }

    function loadData($data)
    {
        $this->text = $data['text'];
    }

    function display()
    {
        if (pnModIsHooked('bbcode', 'content')) {
            $code = '[code]' . $this->text . '[/code]';
            $code = pnModAPIFunc('bbcode', 'user', 'transform', array('extrainfo' => array($code), 'objectid' => 999));
            $this->$code = $code[0];
            return $this->$code;
        } else {
            return $this->transformCode($this->text, true);
        }
    }

    function displayEditing()
    {
        return $this->transformCode($this->text, false); // <pre> does not work in IE 7 with the portal javascript
    }

    function getDefaultData()
    {
        return array('text' => '');
    }

    function getSearchableText()
    {
        return html_entity_decode(strip_tags($this->text));
    }

    function transformCode($code, $usePre)
    {
        $lines = explode("\n", $code);
        $html = "<div class=\"content-computercode\"><ol class=\"codelisting\">\n";

        for ($i = 1, $cou = count($lines); $i <= $cou; ++$i) {
            if ($usePre) {
                $line = empty($lines[$i - 1]) ? ' ' : htmlspecialchars($lines[$i - 1]);
                $line = '<div><pre>' . $line . '</pre></div>';
            } else {
                $line = empty($lines[$i - 1]) ? '&nbsp;' : htmlspecialchars($lines[$i - 1]);
                $line = str_replace(' ', '&nbsp;', $line);
                $line = '<div>' . $line . '</div>';
            }
            $html .= "<li>$line</li>\n";
        }

        $html .= "</ol></div>\n";

        return $html;
    }
}

function postcalendar_contenttypesapi_postcalevents($args)
{
    return new postcalendar_contenttypesapi_postcaleventsPlugin($args['data']);
}