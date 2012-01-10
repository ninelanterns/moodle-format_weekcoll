<?php
/**
 * Collapsed Weeks Information
 *
 * A week based format that solves the issue of the 'Scroll of Death' when a course has many weeks. All
 * weeks have a toggle that displays that week. The current week is displayed by default. One or more
 * weeks can be displayed at any given time. Toggles are persistent on a per browser session per course
 * basis but can be made to perist longer by a small code change. Full installation instructions, code
 * adaptions and credits are included in the 'Readme.txt' file.
 *
 * @package    course/format
 * @subpackage weekcoll
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2009-onwards G J Barnard in respect to modifications of standard weeks format.
 * @author     G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @link       http://docs.moodle.org/en/Collapsed_Weeks_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once($CFG->libdir.'/ajax/ajaxlib.php');

// For persistence of toggles.
require_js(array('yui_yahoo', 'yui_cookie', 'yui_event'));
// Now get the css and JavaScript Lib.  The call to weekcoll_init sets things up for JavaScript to work by understanding the particulars of this course.
?>    
<style type="text/css" media="screen">
/* <![CDATA[ */
    @import url(<?php echo $CFG->wwwroot ?>/course/format/weekcoll/weeks_collapsed.css);
/* ]]> */
</style>
<!--[if lte IE 7]>
    <link rel="stylesheet" type="text/css" href="<?php echo $CFG->wwwroot ?>/course/format/weekcoll/ie-7-hacks.css" media="screen" />
<![endif]-->

<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/course/format/weekcoll/lib_min.js"></script>
<script type="text/javascript">
//<![CDATA[
    weekcoll_init('<?php echo $CFG->wwwroot ?>',
                  '<?php echo preg_replace("/[^A-Za-z0-9]/", "", $SITE->shortname) ?>',
                  '<?php echo $course->id ?>',
                  null); <!-- Expiring Cookie Initialisation - replace 'null' with your chosen duration. -->);
//]]>
</script>
<script type="text/javascript" src="<?php echo $CFG->wwwroot ?>/course/format/weekcoll/wc_section_classes_min.js"></script>

<?php
    $week = optional_param('week', -1, PARAM_INT);

    // Bounds for block widths
    // more flexible for theme designers taken from theme config.php
    $lmin = (empty($THEME->block_l_min_width)) ? 100 : $THEME->block_l_min_width;
    $lmax = (empty($THEME->block_l_max_width)) ? 210 : $THEME->block_l_max_width;
    $rmin = (empty($THEME->block_r_min_width)) ? 100 : $THEME->block_r_min_width;
    $rmax = (empty($THEME->block_r_max_width)) ? 210 : $THEME->block_r_max_width;

    define('BLOCK_L_MIN_WIDTH', $lmin);
    define('BLOCK_L_MAX_WIDTH', $lmax);
    define('BLOCK_R_MIN_WIDTH', $rmin);
    define('BLOCK_R_MAX_WIDTH', $rmax);
  
    $preferred_width_left  = bounded_number(BLOCK_L_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_LEFT]),  
                                            BLOCK_L_MAX_WIDTH);
    $preferred_width_right = bounded_number(BLOCK_R_MIN_WIDTH, blocks_preferred_width($pageblocks[BLOCK_POS_RIGHT]), 
                                            BLOCK_R_MAX_WIDTH);

    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    if ($week != -1) {
        $displaysection = course_set_display($course->id, $week);
    } else {
        if (isset($USER->display[$course->id])) {
            // If we are editing then we can show only one section.
            if (isediting($course->id) && has_capability('moodle/course:update', $context))
            {
                $displaysection = $USER->display[$course->id];
            }
            else
            {
                // Wipe out display section so that when we finish editing and then return we are not confused by
                // only a single section being displayed.
                $displaysection = course_set_display($course->id, 0);
            }
        } else {
            $displaysection = course_set_display($course->id, 0);
        }
    }

    $streditsummary  = get_string('editsummary');
    $stradd          = get_string('add');
    $stractivities   = get_string('activities');
    $strshowallweeks = get_string('showallweeks');
    $strweek         = get_string('week');
    $strgroups       = get_string('groups');
    $strgroupmy      = get_string('groupmy');
    $editing         = $PAGE->user_is_editing();

    if ($editing) {
        $strstudents = moodle_strtolower($course->students);
        $strweekhide = get_string('weekhide', '', $strstudents);
        $strweekshow = get_string('weekshow', '', $strstudents);
        $strmoveup   = get_string('moveup');
        $strmovedown = get_string('movedown');
    }

/// Layout the whole page as three big columns.
    echo '<table id="layout-table" cellspacing="0" summary="'.get_string('layouttable').'"><tr>';
    $lt = (empty($THEME->layouttable)) ? array('left', 'middle', 'right') : $THEME->layouttable;
    foreach ($lt as $column) {
        switch ($column) {
            case 'left':
 
/// The left column ...

    if (blocks_have_content($pageblocks, BLOCK_POS_LEFT) || $editing) {
        echo '<td style="width:'.$preferred_width_left.'px" id="left-column">';

        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_LEFT);
        print_container_end();

        echo '</td>';
    }
            break;
            case 'middle':
/// Start main column
    echo '<td id="middle-column">';

    print_container_start();
        
    echo skip_main_destination();

    print_heading_block(get_string('weeklyoutline'), 'outline');

    echo '<table id="theweeks" class="weeks" width="100%" summary="'.get_string('layouttable').'">';
    echo '<colgroup><col class="left" /><col class="content" /><col class="right" style="'.get_string('weekcolltogglewidth','format_weekcoll').'" /></colgroup>';
    // The string 'weekcolltogglewidth' above can be set in the language file to allow for different lengths of words for different languages.
    // For example $string['weekcolltogglewidth']='width: 42px;' - if not defined, then the default '#theweeks col.right' in weeks_collapsed.css applies.

/// If currently moving a file then show the current clipboard
    if (ismoving($course->id)) {
        $stractivityclipboard = strip_tags(get_string('activityclipboard', '', addslashes($USER->activitycopyname)));
        $strcancel= get_string('cancel');
        echo '<tr class="clipboard">';
        echo '<td colspan="3">';
        echo $stractivityclipboard.'&nbsp;&nbsp;(<a href="mod.php?cancelcopy=true&amp;sesskey='.$USER->sesskey.'">'.$strcancel.'</a>)';
        echo '</td>';
        echo '</tr>';
    }

/// Print Section 0 with general activities

    $section = 0;
    $thissection = $sections[$section];

    if ($thissection->summary or $thissection->sequence or isediting($course->id)) {
        echo '<tr id="section-0" class="section main">';
        echo '<td id="sectionblock-0" class="left side">&nbsp;</td>';  // MDL-18232
        echo '<td class="content">';
        
        echo '<div class="summary">';
        $summaryformatoptions->noclean = true;
        echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);

        if (isediting($course->id) && has_capability('moodle/course:update', $context)) {
            echo '<a title="'.$streditsummary.'" '.
                 ' href="editsection.php?id='.$thissection->id.'"><img src="'.$CFG->pixpath.'/t/edit.gif" '.
                 'class="iconsmall edit" alt="'.$streditsummary.'" /></a><br /><br />';
        }
        echo '</div>';
        
        print_section($course, $thissection, $mods, $modnamesused);

        if (isediting($course->id)) {
            print_section_add_menus($course, $section, $modnames);
        }

        echo '</td>';
        echo '<td class="right side">&nbsp;</td>';
        echo '</tr>';
        echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';

    }

    // Get the specific words from the language files.
    $weektext = get_string('week');
    $toggletext = get_string('weekcolltoggle','format_weekcoll'); // This is defined in lang/en_utf8 of the formats installation directory - basically, the word 'Toggle'.

    // Toggle all.
    echo '<tr id="toggle-all" class="section main">';
    echo '<td class="left side toggle-all" colspan="2">';
    echo '<h4><a class="on" href="#" onclick="all_opened(); return false;">'.get_string('weekcollopened','format_weekcoll').'</a><a class="off" href="#" onclick="all_closed(); return false;">'.get_string('weekcollclosed','format_weekcoll').'</a>'.get_string('weekcollall','format_weekcoll').'</h4>';
    echo '</td>';
    echo '<td class="right side">&nbsp;</td>';
    echo '</tr>';
    echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';

/// Now all the normal modules by week
/// Everything below uses "section" terminology - each "section" is a week.

    $timenow = time();
    $weekdate = $course->startdate;    // this should be 0:00 Monday of that week
    $weekdate += 7200;                 // Add two hours to avoid possible DST problems
    $section = 1;
    $sectionmenu = array();
    $theweek = 0; // The section that will be the current week
    $weekofseconds = 604800;
    $course->enddate = $course->startdate + ($weekofseconds * $course->numsections);

    $strftimedateshort = ' '.get_string('strftimedateshort');

    while ($weekdate < $course->enddate) {

        $nextweekdate = $weekdate + ($weekofseconds);
        $weekday = userdate($weekdate, $strftimedateshort);
        $endweekday = userdate($weekdate+518400, $strftimedateshort);

        if (!empty($sections[$section])) {
            $thissection = $sections[$section];

        } else {
            unset($thissection);
            $thissection->course = $course->id;   // Create a new week structure
            $thissection->section = $section;
            $thissection->summary = '';
            $thissection->visible = 1;
            if (!$thissection->id = insert_record('course_sections', $thissection)) {
                notify('Error inserting new week!');
            }
        }

        $showsection = (has_capability('moodle/course:viewhiddensections', $context) or $thissection->visible or !$course->hiddensections);

        if (!empty($displaysection) and $displaysection != $section) {  // Check this week is visible
            if ($showsection) {
                $sectionmenu['week='.$section] = s("$strweek $section |     $weekday - $endweekday");
            }
            $section++;
            $weekdate = $nextweekdate;
            continue;
        }

        if ($showsection) {

            $currentweek = (($weekdate <= $timenow) && ($timenow < $nextweekdate));

            $currenttext = '';
            if (!$thissection->visible) {
                $sectionstyle = ' hidden';
            } else if ($currentweek) {
                $sectionstyle = ' current';
                $currenttext = get_accesshide(get_string('currentweek','access'));
                $theweek = $section;
            } else {
                $sectionstyle = '';
            }

            $weekperiod = $weekday.' - '.$endweekday;
                        
            echo '<tr class="cps" id="sectionhead-'.$section.'">'; // The table row of the toggle.
            // Have a different look depending on if the section summary has been completed.
            if (empty($thissection->summary)) {
                $thissection->summary='';
                echo '<td colspan="3"><a id="sectionatag-'.$section.'" class="cps_nosumm" href="#" onclick="toggle_week(this,'.$section.'); return false;"><span>';
                echo stripslashes_safe($weekperiod);
                echo '</span><br />'.$weektext.' '.$currenttext.$section.' - '.$toggletext.'</a></td>';
            } else {
                echo '<td colspan="2"><a id="sectionatag-'.$section.'" href="#" onclick="toggle_week(this,'.$section.'); return false;"><span>';
                echo stripslashes_safe($weekperiod);
                echo '<br />'.html_to_text($thissection->summary).'</span> - '.$toggletext.'</a></td><td class="cps_centre">'.$weektext.'<br />'.$currenttext.$section.'</td>';
                  // Comment out the above three lines and uncomment the lines below if you do not want 'Week x' displayed on the right hand side of the toggle.
                //echo '<td colspan="3"><a id="sectionatag-'.$section.'" href="#" onclick="toggle_week(this,'.$section.'); return false;"><span>';
                //echo stripslashes_safe($weekperiod);
                //echo '<br />'.html_to_text($thissection->summary).'</span> - '.$toggletext.'</a></td>';

            }
            echo '</tr>';

            // Now the section itself.  The css class of 'hid' contains the display attribute that manipulated by the JavaScript to show and hide the section.  It is defined in js-override-weekcoll.css which 
            // is loaded into the DOM by the JavaScript function weekcoll_init.  Therefore having a logical separation between static and JavaScript manipulated css.  Nothing else here differs from 
            // the standard Weeks format in the core distribution.  The next change is at the bottom.
            echo '<tr id="section-'.$section.'" class="section main'.$sectionstyle.' hid">';
            echo '<td id="sectionblock-'.$section.'" class="left side">&nbsp;'.$currenttext.$section.'</td>';  // MDL-18232
            // Comment out the above line and uncomment the line below if you do not want the section number displayed on the left hand side of the section.
            //echo '<td class="left side">&nbsp;</td>';


            echo '<td class="content">';
            if (!has_capability('moodle/course:viewhiddensections', $context) and !$thissection->visible) {   // Hidden for students
                print_heading($weekperiod.' ('.get_string('notavailable').')', null, 3, 'weekdates');

            } else {
                echo '<div class="summary">';

                if (isediting($course->id) && has_capability('moodle/course:update', $context)) {
                    print_heading($weekperiod, null, 3, 'weekdates');
                    $summaryformatoptions->noclean = true;
                    echo format_text($thissection->summary, FORMAT_HTML, $summaryformatoptions);
                    echo ' <a title="'.$streditsummary.'" href="editsection.php?id='.$thissection->id.'">'.
                         '<img src="'.$CFG->pixpath.'/t/edit.gif" class="iconsmall edit" alt="'.$streditsummary.'" /></a><br /><br />';
                }
                echo '</div>';
                
                print_section($course, $thissection, $mods, $modnamesused);

                if (isediting($course->id)) {
                    print_section_add_menus($course, $section, $modnames);
                }
            }
            echo '</td>';

            echo '<td class="right side">';

            if (isediting($course->id) && has_capability('moodle/course:update', $context)) {
            // Only contemplate allowing a single viewable section when editing, other situations confusing!
                if ($displaysection == $section) {
                    echo '<a href="view.php?id='.$course->id.'&amp;week=0#section-'.$section.'" title="'.$strshowallweeks.'">'.
                         '<img src="'.$CFG->pixpath.'/i/all.gif" class="icon wkall" alt="'.$strshowallweeks.'" /></a><br />';
                } else {
                    $strshowonlyweek = get_string("showonlyweek", "", $section);
                    echo '<a href="view.php?id='.$course->id.'&amp;week='.$section.'" title="'.$strshowonlyweek.'">'.
                         '<img src="'.$CFG->pixpath.'/i/one.gif" class="icon wkone" alt="'.$strshowonlyweek.'" /></a><br />';
                }
            }

            if (isediting($course->id) && has_capability('moodle/course:update', $context)) {
                if ($thissection->visible) {        // Show the hide/show eye
                    echo '<a href="view.php?id='.$course->id.'&amp;hide='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strweekhide.'">'.
                         '<img src="'.$CFG->pixpath.'/i/hide.gif" class="icon hide" alt="'.$strweekhide.'" /></a><br />';
                } else {
                    echo '<a href="view.php?id='.$course->id.'&amp;show='.$section.'&amp;sesskey='.$USER->sesskey.'#section-'.$section.'" title="'.$strweekshow.'">'.
                         '<img src="'.$CFG->pixpath.'/i/show.gif" class="icon hide" alt="'.$strweekshow.'" /></a><br />';
                }
                if ($section > 1) {                       // Add a arrow to move section up
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=-1&amp;sesskey='.$USER->sesskey.'#section-'.($section-1).'" title="'.$strmoveup.'">'.
                         '<img src="'.$CFG->pixpath.'/t/up.gif" class="iconsmall up" alt="'.$strmoveup.'" /></a><br />';
                }

                if ($section < $course->numsections) {    // Add a arrow to move section down
                    echo '<a href="view.php?id='.$course->id.'&amp;random='.rand(1,10000).'&amp;section='.$section.'&amp;move=1&amp;sesskey='.$USER->sesskey.'#section-'.($section+1).'" title="'.$strmovedown.'">'.
                         '<img src="'.$CFG->pixpath.'/t/down.gif" class="iconsmall down" alt="'.$strmovedown.'" /></a><br />';
                }
            }

            echo '</td></tr>';
            echo '<tr class="section separator"><td colspan="3" class="spacer"></td></tr>';
        }

        $section++;
        $weekdate = $nextweekdate;
    }
    echo '</table>';

    if (!empty($sectionmenu)) {
        echo '<div class="jumpmenu">';
        echo popup_form($CFG->wwwroot.'/course/view.php?id='.$course->id.'&amp;', $sectionmenu,
                   'sectionmenu', '', get_string('jumpto'), '', '', true);
        echo '</div>';
    }

    print_container_end();

    echo '</td>';

            break;
            case 'right':
    // The right column
    if (blocks_have_content($pageblocks, BLOCK_POS_RIGHT) || $editing) {
        echo '<td style="width: '.$preferred_width_right.'px;" id="right-column">';

        print_container_start();
        blocks_print_group($PAGE, $pageblocks, BLOCK_POS_RIGHT);
        print_container_end();

        echo '</td>';
    }

            break;
        }
    }
    echo '</tr></table>';
    // Establish persistance when  we have loaded!
    ?>
    <script type="text/javascript" defer="defer"> // Defer running of the script until all HMTL has been passed.
//<![CDATA[
    <?php
    echo 'set_number_of_toggles('.$course->numsections.');'; // Tell JavaScript how many Toggles to reset.    
    echo 'set_current_week('.$theweek.');'; // Ensure that the current week is always open.
    // Restore the state of the toggles from the cookie if not in 'Show week x' mode, otherwise show that week.
    if ($displaysection == 0)
    {
        echo 'YAHOO.util.Event.onDOMReady(reload_toggles);';
        // TODO: Use below later instead of above, for reason see below for save_toggles.
        //echo 'window.addEventListener("load",reload_toggles,false);'; 
    }
    else
    {
        echo 'show_week('.$displaysection.');';
    }
    // Save the state of the toggles when the page unloads.  This is a stopgap as toggle state is saved every time
    // they change.  This is because there is no 'refresh' event yet which would be the best implementation.
    // TODO: Comment line 58 (save_toggles call in togglebinary function) of lib.js and make into lib_min.js when
    //       IE9 fully established with proper DOM event handling -
    //       http://blogs.msdn.com/ie/archive/2010/03/26/dom-level-3-events-support-in-ie9.aspx &
    //       http://dev.w3.org/2006/webapi/DOM-Level-3-Events/html/DOM3-Events.html#event-types-list
    //echo 'window.addEventListener("unload",save_toggles,false);';  TODO Comment
    ?>
//]]>
</script>
