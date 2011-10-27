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
 *
 * @package    local
 * @subpackage snapp
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Moodle Gadget "class" Connections to Moodle Gadget services Example: var mg =
 * new moodle_gadget("%%moodleurl%%");
 */
var _mg = null;//Internal pointer to moodle_gadget instance - use this when scope is an issue

function moodle_gadget(moodleurl) {
    if (_mg == null) {
        _mg = this;
    }

    this.moodleurl = moodleurl;

    this.usermap = false;// Is there a user 'mapping' to Moodle? Set by user_map_exist();

    this.user_map_exists = function() {

        if (_mg.usermap) {
            return;// Already done
        }
        this.call_moodle('/local/snapp/gadgetserv/mapuser.php',
            function(ret)  {
                // Checks what Moodle returns
                if (typeof ret.status == 'undefined' || ret.status != 200) {
                    //general network error (often happens in igoogle as bug where auth token expires)
                    var stringy = 'There was a network error when trying to get information for the gadget. Please refresh the page to try again.';
                    error(stringy);
                    return;
                }
                // Debugging info
                if (typeof ret.content.debuginfo != 'undefined') {
                    debug(ret.content.debuginfo);
                }
                if (typeof ret.content.userexists != 'undefined') {
                    if (ret.content.userexists) {
                        _mg.usermap = true;
                        return;// Simple -user exists so stop
                    }
                }
                // Not mapped, so check for any errors first
                if (typeof ret.error != 'undefined') {
                    // General error
                    error(ret.error);
                    return;
                }
                if (typeof ret.content.message != 'undefined') {
                    // Error from Moodle
                    error(ret.content.message);
                    return;
                }
                // Everything OK - but no mapping - give user instructions
                YUI().use('node', function(Y) {
                    var um = Y.one('#usermap');// allow user defined div
                    if (!um) {
                        Y.one('body').prepend('<div id="usermap"> </div>');
                    }
                    var message = '<p>' + ret.content.instructions;
                    if (typeof ret.content.url != 'undefined') {
                        message += '<a id="usermap_link" href="'+ret.content.url+'">'+ ret.content.linktext +'</a>.';
                    }
                    message += '</p>';
                    if (um.get('innerHTML').indexOf(ret.content.instructions) == -1) {
                        //only write once
                        um.set('innerHTML', message);
                        var linky = Y.one('#usermap_link');
                        linky.on('click', function(e) {
                            window.open(this.get('href'), '_blank');
                            e.preventDefault();
                            //Keep checking by making more calls to this function
                            YUI().use('async-queue', function(Y) {
                                var q = new Y.AsyncQueue({
                                    fn:function() {_mg.user_map_exists();}
                                    });
                                q.defaults.iterations = 500;
                                q.defaults.timeout = 3000;
                                q.defaults.until = function() {
                                    if (_mg.usermap) {
                                        q.stop();
                                        YUI().use('node', function(Y) {
                                            Y.one('#usermap').setStyle('display', 'none');
                                        });
                                        gadgets.util.runOnLoadHandlers();
                                        return true;
                                    }
                                    return false;
                                };
                                q.run();
                            });
                        });
                    }
                });
            }
        );
    };

    /**
     * Call a Moodle webservice as defined in wsfunction wsparams is a string of
     * what params you would normally send to the web service callback is the
     * function you what to send the result to cache is optional - set to true
     * to use a cached call
     */
    this.web_service_call = function(wsfunction, wsparams, callback, cache) {

        /**
         * Function used to sit between webservice result and final callback
         * Will create UI if any errors sent from Moodle
         */
        this.wscallback = function(ret) {
            // If errors from call do not call callback; show error instead
            if (typeof ret.error != 'undefined') {
                // General error
                error(ret.error);
                return;
            } else if (typeof ret.content.message != 'undefined') {
                // Error from Moodle
                error(ret.content.message);
                return;
            }
            callback(ret);
        };

        wsparams = escape(wsparams);

        var url = '/local/snapp/gadgetserv/call_ws.php?wsfunction=' + wsfunction;
        url += '&wsparams=' + wsparams;

        this.call_moodle(url, this.wscallback, cache);
    };

    /**
     * Call a Moodle url with a OAuth signed request (not cached)
     */
    this.call_moodle = function(rurl, callback, cache) {
        if (typeof cache == 'undefined') {
            cache = false;
        }

        // check relative url has fwdslash
        if (!rurl.charAt(0) != '/') {
            rurl = '/' + rurl;
        }

        var params = {
                'href' : this.moodleurl + rurl,
                'format' : 'json',
                'authz' : 'signed'
           };
        if (!cache) {
           params.refreshInterval = 0;
        }

        osapi.http.get(params).execute(callback);
    };
}

function debug(content) {
    YUI().use('node','event-key','overlay', function(Y) {
        var debugnode = Y.one('#debug');
        if (!debugnode) {
            Y.one('body').append('<div id="debug">'+ content +'</div>');
            debugnode = Y.one('#debug');
            debugnode.setStyle('background-color', '#fff');
            debugnode.setStyle('display', 'none');
            var overlay = new Y.Overlay({
                srcNode:"#debug",
                visible:false,
                width:"40em"
            });
            overlay.render();
            Y.on('key', function(e) {
                // stopPropagation() and preventDefault()
                e.halt();
                overlay.set('visible', true);
                Y.one('#debug').setStyle('display', 'block');
            }, 'body', 'down:68', Y);
            Y.on('key', function(e) {
                // stopPropagation() and preventDefault()
                e.halt();
                overlay.set('visible', false);
                Y.one('#debug').setStyle('display', 'none');
            }, 'body', 'up:68', Y);
            return;
        }
        var acontent = debugnode.get('innerHTML') + content;
        debugnode.set('innerHTML', acontent);
    });
}

function error(message) {
    YUI().use('node', 'yui2-resize', 'yui2-dragdrop', 'yui2-container', 'yui2-button',
            'yui2-layout', 'yui2-event', function(Y) {
        // Instantiate a Panel from script
        var errordiv = Y.one('#error');
        if (!errordiv) {
            Y.one('body').append('<div id="error"> </div>');
            Y.one('body').addClass('yui-skin-sam');
        }
        //YAHOO var doesn't seem to work use Y.YUI2 instead
        var errorpan = new Y.YUI2.widget.Panel("panel2", { width:"320px", visible:true,
            draggable:true, close:false, y:0, constraintoviewport:true } );
        errorpan.setHeader("Warning");
        errorpan.setBody(message);
        errorpan.render("error");
    });
}
