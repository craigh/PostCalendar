<?php
/**
 * SVN: $Id$
 *
 * @package     PostCalendar
 * @author      $Author$
 * @link        $HeadURL$
 * @version     $Revision$
 *
 * PostCalendar::Zikula Events Calendar Module
 * Copyright (C) 2002  The PostCalendar Team
 * http://postcalendar.tv
 * Copyright (C) 2009  Sound Web Development
 * Craig Heydenburg
 * http://code.zikula.org/soundwebdevelopment/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * To read the license please read the docs/license.txt or visit
 * http://www.gnu.org/copyleft/gpl.html
 *
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
function smarty_function_pc_popup($args)
{
    // if we're not using popups just return an empty string
    if (!_SETTING_USE_POPUPS) return;

    if (empty($args['text']) && !isset($args['inarray']) && empty($args['function']))
        $args['text'] = "overlib: attribute 'text' or 'inarray' or 'function' required";

    if (empty($args['trigger'])) $args['trigger'] = " onMouseOver";

    echo $args['trigger'] . '="return overlib(\'' . pc_clean($args['text']) . '\'';
    if ($args['sticky']) {
        echo ",STICKY";
    }
    if (!empty($args['caption'])) {
        echo ",CAPTION,'" . pc_clean($args['caption']) . "'";
    }
    if (!empty($args['fgcolor'])) {
        echo ",FGCOLOR,'{$args['fgcolor']}'";
    }
    if (!empty($args['bgcolor'])) {
        echo ",BGCOLOR,'{$args['bgcolor']}'";
    }
    if (!empty($args['textcolor'])) {
        echo ",TEXTCOLOR,'{$args['textcolor']}'";
    }
    if (!empty($args['capcolor'])) {
        echo ",CAPCOLOR,'{$args['capcolor']}'";
    }
    if (!empty($args['closecolor'])) {
        echo ",CLOSECOLOR,'{$args['closecolor']}'";
    }
    if (!empty($args['textfont'])) {
        echo ",TEXTFONT,'{$args['textfont']}'";
    }
    if (!empty($args['captionfont'])) {
        echo ",CAPTIONFONT,'{$args['captionfont']}'";
    }
    if (!empty($args['closefont'])) {
        echo ",CLOSEFONT,'{$args['closefont']}'";
    }
    if (!empty($args['textsize'])) {
        echo ",TEXTSIZE,{$args['textsize']}";
    }
    if (!empty($args['captionsize'])) {
        echo ",CAPTIONSIZE,{$args['captionsize']}";
    }
    if (!empty($args['closesize'])) {
        echo ",CLOSESIZE,{$args['closesize']}";
    }
    if (!empty($args['width'])) {
        echo ",WIDTH,{$args['width']}";
    }
    if (!empty($args['height'])) {
        echo ",HEIGHT,{$args['height']}";
    }
    if (!empty($args['left'])) {
        echo ",LEFT";
    }
    if (!empty($args['right'])) {
        echo ",RIGHT";
    }
    if (!empty($args['center'])) {
        echo ",CENTER";
    }
    if (!empty($args['above'])) {
        echo ",ABOVE";
    }
    if (!empty($args['below'])) {
        echo ",BELOW";
    }
    if (isset($args['border'])) {
        echo ",BORDER,{$args['border']}";
    }
    if (isset($args['offsetx'])) {
        echo ",OFFSETX,{$args['offsetx']}";
    }
    if (isset($args['offsety'])) {
        echo ",OFFSETY,{$args['offsety']}";
    }
    if (!empty($args['fgbackground'])) {
        echo ",FGBACKGROUND,'{$args['fgbackground']}'";
    }
    if (!empty($args['bgbackground'])) {
        echo ",BGBACKGROUND,'{$args['bgbackground']}'";
    }
    if (!empty($args['closetext'])) {
        echo ",CLOSETEXT,'" . pc_clean($args['closetext']) . "'";
    }
    if (!empty($args['noclose'])) {
        echo ",NOCLOSE";
    }
    if (!empty($args['status'])) {
        echo ",STATUS,'" . pc_clean($args['status']) . "'";
    }
    if (!empty($args['autostatus'])) {
        echo ",AUTOSTATUS";
    }
    if (!empty($args['autostatuscap'])) {
        echo ",AUTOSTATUSCAP";
    }
    if (isset($args['inarray'])) {
        echo ",INARRAY,'{$args['inarray']}'";
    }
    if (isset($args['caparray'])) {
        echo ",CAPARRAY,'{$args['caparray']}'";
    }
    if (!empty($args['capicon'])) {
        echo ",CAPICON,'{$args['capicon']}'";
    }
    if (!empty($args['snapx'])) {
        echo ",SNAPX,{$args['snapx']}";
    }
    if (!empty($args['snapy'])) {
        echo ",SNAPY,{$args['snapy']}";
    }
    if (isset($args['fixx'])) {
        echo ",FIXX,{$args['fixx']}";
    }
    if (isset($args['fixy'])) {
        echo ",FIXY,{$args['fixy']}";
    }
    if (!empty($args['background'])) {
        echo ",BACKGROUND,'{$args['background']}'";
    }
    if (!empty($args['padx'])) {
        echo ",PADX,{$args['padx']}";
    }
    if (!empty($args['pady'])) {
        echo ",PADY,{$args['pady']}";
    }
    if (!empty($args['fullhtml'])) {
        echo ",FULLHTML";
    }
    if (!empty($args['frame'])) {
        echo ",FRAME,'{$args['frame']}'";
    }
    if (isset($args['timeout'])) {
        echo ",TIMEOUT,{$args['timeout']}";
    }
    if (!empty($args['function'])) {
        echo ",FUNCTION,'{$args['function']}'";
    }
    if (isset($args['delay'])) {
        echo ",DELAY,{$args['delay']}";
    }
    if (!empty($args['hauto'])) {
        echo ",HAUTO";
    }
    if (!empty($args['vauto'])) {
        echo ",VAUTO";
    }
    echo ');" onMouseOut="nd();"';
}

/**
 * pc_clean
 * @param s string text to clean
 * @return string cleaned up text
 */
function pc_clean($s)
{
    $display_type = substr($s, 0, 6);

    if ($display_type == ':text:') $s = substr($s, 6);
    elseif ($display_type == ':html:') $s = substr($s, 6);

    unset($display_type);
    $s = preg_replace('/[\r|\n]/i', '', $s);
    $s = str_replace("'", "\'", $s);
    $s = str_replace('"', '&quot;', $s);
    // break really long lines - only break at spaces to allow for
    // correct interpretation of special characters
    $tmp = explode(' ', $s);
    return join("'+' ", $tmp);
}