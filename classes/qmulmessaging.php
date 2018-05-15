<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Main class for the widget
 *
 * @package   widgettype_qmulmessaging
 * @copyright 2017 Davo Smith, Synergy Learning
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace widgettype_qmulmessaging;

use local_qmul_messaging;

defined('MOODLE_INTERNAL') || die();

class qmulmessaging extends \block_widgets\widgettype_base {

    private $systemcontextid = 1;

    /**
     * Get the title to display for this widget.
     * @return string
     */
    public function get_title_internal() {
        return get_string('titlename', 'widgettype_qmulmessaging');
    }

    /**
     * Return the main content for the widget.
     * @return string[]
     */
    public function get_items() {
        global $USER, $OUTPUT, $CFG;

        $context = \context_system::instance();

        $messageslist = new local_qmul_messaging\messagelist($context, $USER);
        $messageslist->get_message_list_by_user($USER->id);
        $messages = $messageslist->messagelist;

        $ret = [];
        //now render
        if(!$messages){
            $ret[] = get_string('nonotices', 'widgettype_qmulmessaging');
            return $ret;
        }

        $markasreadarray = (array) $messageslist->markasreadlist;

        foreach ($messages as $message) {

            if($message->hidden != 0){
                continue;
            }

            $message->unread = 'bold';
            foreach ($markasreadarray as $element) {
                if($element === $message->messageid){
                    //message is read
                    $message->unread = '';
                    break;
                }
            }

            //prepare some values for display
            $message->subject = $this->display_message_text($message->subject, 35);
            $message->catname = $this->display_message_text($message->catname, 35);
            if($message->catname !== $messageslist::$sitewideref){
                $message->catname = "in " . $message->catname;
            }

            $urlparams = array('context' => $message->context, 'message' =>  $message->messageid);
            $viewurl = new \moodle_url("{$CFG->wwwroot}/local/qmul_messaging/view.php", $urlparams);
            $message->viewurl = $viewurl->out(false);
            $ret[] = $OUTPUT->render_from_template('widgettype_qmulmessaging/messaging', $message);
        }
        return $ret;
    }

    protected function display_message_text($text, $texttrimlength){

        $text = format_text(strip_tags($text), FORMAT_PLAIN);
        $textlength = strlen($text);
        $text = $this->tokenTruncate($text, $texttrimlength);
        $trimedlength = strlen($text);
        if($trimedlength < $textlength){
            $text = $text . " ...";
        }
        return $text;
    }

    protected function tokenTruncate($text, $texttrimlength) {
        $parts = preg_split('/([\s\n\r]+)/', $text, null, PREG_SPLIT_DELIM_CAPTURE);
        $parts_count = count($parts);

        $length = 0;
        $last_part = 0;
        for (; $last_part < $parts_count; ++$last_part) {
            $length += strlen($parts[$last_part]);
            if ($length > $texttrimlength) { break; }
        }

        return implode(array_slice($parts, 0, $last_part));
    }

    /**
     * Return the footer content for the widget.
     * @return string
     */
    public function get_footer() {
        global  $CFG;

        $url = new \moodle_url("{$CFG->wwwroot}/local/qmul_messaging/index.php", ['context' => 1]);
        $out =  \html_writer::link($url, 'View inbox', array('class' => 'btn btn-primary'));
        return $out;
    }
}
