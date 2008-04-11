<?php
/**
 * DokuWiki Action Plugin Feedmod
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Klier <chi@chimeric.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');
if(!defined('DOKU_LF')) define('DOKU_LF', "\n");

require_once(DOKU_PLUGIN.'action.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class action_plugin_feedmod extends DokuWiki_Action_Plugin {

    function getInfo() {
        return array(
                'author' => 'Michael Klier',
                'email'  => 'chi@chimeric.de',
                'date'   => '2008-04-09',
                'name'   => 'feedmod',
                'desc'   => 'Modifies feed items for nicer full html feeds.',
                'url'    => 'http://www.chimeric.de/projects/dokuwiki/plugin/feedmod'
            );
    }

    // register hook
    function register(&$controller) {
        $controller->register_hook('FEED_ITEM_ADD', 'BEFORE', $this, '_feedmod');
    }

    /**
     * Removes the headline which is already set in the item title and adds a link
     * to the discussion section if a discussion exists
     *
     * @author Michael Klier <chi@chimeric.de>
     */
    function _feedmod(&$event, $param) {
        global $conf;
        global $opt;    // options feed.php is called with

        if($opt['item_content'] == 'html') {

            // only act if were linking to the current version of the page
            if($opt['link_to'] == 'current') {

                $url = parse_url($event->data['item']->link);
                $base_url = getBaseURL();

                // determine page id by rewrite mode
                switch($conf['userewrite']) {

                    case 0:
                        preg_match('#id=([^&]*)#', $url['query'], $match);
                        if($base_url != '/') {
                            $id = cleanID(str_replace($base_url, '', $match[1]));
                        } else {
                            $id = cleanID($match[1]);
                        }
                        break;

                    case 1:
                        if($base_url != '/') {
                            $id = cleanID(str_replace('/',':',str_replace($base_url, '', $url['path'])));
                        } else {
                            $id = cleanID(str_replace('/',':', $url['path']));
                        }
                        break;

                    case 2:
                        preg_match('#doku.php/([^&]*)#', $url['path'], $match);
                        if($base_url != '/') {
                            $id = cleanID(str_replace($base_url, '', $match[1]));
                        } else {
                            $id = cleanID($match[1]);
                        }
                        break;
                }

                // strip first heading and replace item title
                $firstheading = p_get_metadata($id, 'title', false);
                $event->data['item']->description = preg_replace('#[^\n]*' . htmlspecialchars($firstheading) . '.*\n#', '', $event->data['item']->description);
                $event->data['item']->title = $firstheading;

                // check for discussion file
                if(@file_exists(metaFN($id, '.comments'))) {
                    $clink  = '<span class="plugin_feedmod_comments">' . DOKU_LF;
                    $clink .= '  <a href="' . $event->data['item']->link . '#discussion__section" title="'. $this->getLang('comments') . '">' . $this->getLang('comments') . '</a>' . DOKU_LF;
                    $clink .= '</span>' . DOKU_LF;
                    $event->data['item']->description .= $clink;
                }

                // check for file footer and append it
                if(@file_exists(DOKU_PLUGIN.'feedmod/_footer.txt')) {
                    $footer = file_get_contents(DOKU_PLUGIN.'feedmod/_footer.txt');
                    $footer = str_replace('@URL@', $event->data['item']->link, $footer);
                    $footer = str_replace('@PAGE@', $id, $footer);
                    $footer = str_replace('@TITLE@', $event->data['item']->title, $footer);
                    $footer = str_replace('@AUTHOR@', $event->data['item']->author, $footer);
                    $event->data['item']->description .= $footer;
                }
            } 
        }
    }
}

// vim:ts=4:sw=4:enc=utf-8:
