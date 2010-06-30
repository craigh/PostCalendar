<?php
/**
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Id$
 * @copyright   Copyright (c) 2002, The PostCalendar Team
 * @copyright   Copyright (c) 2009, Craig Heydenburg, Sound Web Development
 * @license     http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

/**
 * Define popups
 *
 * @param array $args Array of function arguments.
 *                    Required keys: text, inarray, function
 *                    Optional keys: trigger, sticky, caption, fgcolor, bgcolor, textcolor, capcolor, closecolor,
 *                    textfont, captionfont, closefont, textsize, captionsize, closesize,
 *                    width, height, left, right, center, above, below, border, offsetx, offsety,
 *                    fgbackground, bgbackground, closetext, noclose, status, autostatus, autostatuscap,
 *                    caparray, capicon, snapx, snapy, fixx, fixy, background, padx, pady,
 *                    fullhtml, frame, timeout, delay, hauto, vauto
 */
function smarty_function_pc_popup($args, &$smarty)
{
    // if we're not using popups just return an empty string
    if (!_SETTING_USE_POPUPS) {
        return '';
   }

    $dom = ZLanguage::getModuleDomain('PostCalendar');

    if (empty($args['text']) && !isset($args['inarray']) && empty($args['function'])) {
        $args['text'] = __("overlib: attribute 'text' or 'inarray' or 'function' not present", $dom);
   }
    if (empty($args['trigger'])) {
        $args['trigger'] = " onmouseover";
   }
    if ((empty($args['capcolor'])) && (!empty($args['bgcolor']))) {
        $args['capcolor'] = ModUtil::apiFunc('PostCalendar', 'event', 'color_inverse', $args['bgcolor']);
   }

    $ret_val = "";
    $ret_val .= $args['trigger'] . '="return overlib(\'' . pc_clean($args['text']) . '\'';
    if (isset($args['sticky'])) {
        $ret_val .= ",STICKY";
   }
    if (!empty($args['caption'])) {
        $ret_val .= ",CAPTION,'" . pc_clean($args['caption']) . "'";
   }
    if (!empty($args['fgcolor'])) {
        $ret_val .= ",FGCOLOR,'{$args['fgcolor']}'";
   }
    if (!empty($args['bgcolor'])) {
        $ret_val .= ",BGCOLOR,'" . $args['bgcolor'] . "'";
   }
    if (!empty($args['textcolor'])) {
        $ret_val .= ",TEXTCOLOR,'{$args['textcolor']}'";
   }
    if (!empty($args['capcolor'])) {
        $ret_val .= ",CAPCOLOR,'{$args['capcolor']}'";
   }
    if (!empty($args['closecolor'])) {
        $ret_val .= ",CLOSECOLOR,'{$args['closecolor']}'";
   }
    if (!empty($args['textfont'])) {
        $ret_val .= ",TEXTFONT,'{$args['textfont']}'";
   }
    if (!empty($args['captionfont'])) {
        $ret_val .= ",CAPTIONFONT,'{$args['captionfont']}'";
   }
    if (!empty($args['closefont'])) {
        $ret_val .= ",CLOSEFONT,'{$args['closefont']}'";
   }
    if (!empty($args['textsize'])) {
        $ret_val .= ",TEXTSIZE,{$args['textsize']}";
   }
    if (!empty($args['captionsize'])) {
        $ret_val .= ",CAPTIONSIZE,{$args['captionsize']}";
   }
    if (!empty($args['closesize'])) {
        $ret_val .= ",CLOSESIZE,{$args['closesize']}";
   }
    if (!empty($args['width'])) {
        $ret_val .= ",WIDTH,{$args['width']}";
   }
    if (!empty($args['height'])) {
        $ret_val .= ",HEIGHT,{$args['height']}";
   }
    if (!empty($args['left'])) {
        $ret_val .= ",LEFT";
   }
    if (!empty($args['right'])) {
        $ret_val .= ",RIGHT";
   }
    if (!empty($args['center'])) {
        $ret_val .= ",CENTER";
   }
    if (!empty($args['above'])) {
        $ret_val .= ",ABOVE";
   }
    if (!empty($args['below'])) {
        $ret_val .= ",BELOW";
   }
    if (isset($args['border'])) {
        $ret_val .= ",BORDER,{$args['border']}";
   }
    if (isset($args['offsetx'])) {
        $ret_val .= ",OFFSETX,{$args['offsetx']}";
   }
    if (isset($args['offsety'])) {
        $ret_val .= ",OFFSETY,{$args['offsety']}";
   }
    if (!empty($args['fgbackground'])) {
        $ret_val .= ",FGBACKGROUND,'{$args['fgbackground']}'";
   }
    if (!empty($args['bgbackground'])) {
        $ret_val .= ",BGBACKGROUND,'{$args['bgbackground']}'";
   }
    if (!empty($args['closetext'])) {
        $ret_val .= ",CLOSETEXT,'" . pc_clean($args['closetext']) . "'";
   }
    if (!empty($args['noclose'])) {
        $ret_val .= ",NOCLOSE";
   }
    if (!empty($args['status'])) {
        $ret_val .= ",STATUS,'" . pc_clean($args['status']) . "'";
   }
    if (!empty($args['autostatus'])) {
        $ret_val .= ",AUTOSTATUS";
   }
    if (!empty($args['autostatuscap'])) {
        $ret_val .= ",AUTOSTATUSCAP";
   }
    if (isset($args['inarray'])) {
        $ret_val .= ",INARRAY,'{$args['inarray']}'";
   }
    if (isset($args['caparray'])) {
        $ret_val .= ",CAPARRAY,'{$args['caparray']}'";
   }
    if (!empty($args['capicon'])) {
        $ret_val .= ",CAPICON,'{$args['capicon']}'";
   }
    if (!empty($args['snapx'])) {
        $ret_val .= ",SNAPX,{$args['snapx']}";
   }
    if (!empty($args['snapy'])) {
        $ret_val .= ",SNAPY,{$args['snapy']}";
   }
    if (isset($args['fixx'])) {
        $ret_val .= ",FIXX,{$args['fixx']}";
   }
    if (isset($args['fixy'])) {
        $ret_val .= ",FIXY,{$args['fixy']}";
   }
    if (!empty($args['background'])) {
        $ret_val .= ",BACKGROUND,'{$args['background']}'";
   }
    if (!empty($args['padx'])) {
        $ret_val .= ",PADX,{$args['padx']}";
   }
    if (!empty($args['pady'])) {
        $ret_val .= ",PADY,{$args['pady']}";
   }
    if (!empty($args['fullhtml'])) {
        $ret_val .= ",FULLHTML";
   }
    if (!empty($args['frame'])) {
        $ret_val .= ",FRAME,'{$args['frame']}'";
   }
    $ret_val .= array_key_exists('timeout', $args) && !empty($args['timeout']) ? ",TIMEOUT,{$args['timeout']}" : ",TIMEOUT,3600";
    if (!empty($args['function'])) {
        $ret_val .= ",FUNCTION,'{$args['function']}'";
   }
    $ret_val .= array_key_exists('delay', $args) && !empty($args['delay']) ? ",DELAY,{$args['delay']}" : ",DELAY,500";
    if (!empty($args['hauto'])) {
        $ret_val .= ",HAUTO";
   }
    if (!empty($args['vauto'])) {
        $ret_val .= ",VAUTO";
   }
    $ret_val .= ');" onmouseout="nd();"';

    if (isset($args['assign'])) {
        $smarty->assign($args['assign'], $ret_val);
   } else {
        return $ret_val;
   }
}

/**
 * pc_clean
 * @param s string text to clean to prepare as javascript string.
 * @return string cleaned up text
 */
function pc_clean($s)
{
    $display_type = substr($s, 0, 6);

    if ($display_type == ':text:') {
        $s = substr($s, 6);
        $s = nl2br(strip_tags($s));
   } elseif ($display_type == ':html:') {
        $s = substr($s, 6);
   }

    unset($display_type);
    $s = preg_replace('/[\r|\n]/i', '', $s);
    $s = str_replace('"', '&quot;', $s);
    //$s = str_replace("'", "\'", $s);
    $s = addslashes($s);
    // break really long lines - only break at spaces to allow for
    // correct interpretation of special characters
    $tmp = explode(' ', $s);
    return join("'+' ", $tmp);
}