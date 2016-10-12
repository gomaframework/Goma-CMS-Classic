
if (goma.Pusher === undefined) {
    goma.Pusher = (function () {
        'use strict';

        var js = "//js.pusher.com/3.0/pusher.min.js";
        return {

            init: function (pub_key, options) {
                goma.Pusher.key = pub_key;
                goma.Pusher.options = options;
            },

            subscribe: function (id, fn) {
                console.log && console.log("subscribe " + id);
                if (!goma.Pusher.channel(id)) {
                    var cid = id;
                    if (cid === undefined) {
                        return false;
                    }

                    if (fn === undefined) {
                        //throw new Error("subscribing without function is not supported");
                        fn = function(){};
                    }

                    if (goma.Pusher.key !== undefined) {
                        if (goma.Pusher.pusher !== undefined) {
                            fn.apply(goma.Pusher.pusher.subscribe(id));
                        } else {
                            $.getScript(js, function () {
                                goma.Pusher.pusher = new Pusher(goma.Pusher.key, goma.Pusher.options);
                                Pusher.channel_auth_endpoint = root_path + 'pusher/auth';
                                goma.Pusher.pusher.connection.bind('connected', function() {
                                    fn.apply(goma.Pusher.pusher.subscribe(cid));
                                });
                            });
                            return true;
                        }
                    } else {
                        return false;
                    }
                } else {
                    if (fn !== undefined) {
                        fn.apply(goma.Pusher.channel(id));
                    }

                    return true;
                }
            },
            unsubscribe: function (id) {
                if (goma.Pusher.pusher !== undefined) {
                    goma.Pusher.pusher.unsubscribe(id);
                }
            },
            channel: function (id) {
                if (id === undefined) {
                    id = "presence-goma";
                }

                if (goma.Pusher.pusher !== undefined) {
                    return goma.Pusher.pusher.channel(id);
                }

                return false;
            }
        };
    })(jQuery);
}
