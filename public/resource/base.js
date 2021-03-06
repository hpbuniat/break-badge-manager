$(function() {
    var $container = $('div.container'),
        $modal = $('#badgeModal'),
        $form = $('#badgeForm');
    $form.on('click', 'button', function() {
        var $this = $(this);
        $form.append($('<input/>', {
            type: "hidden",
            value: $this.data('id'),
            name: 'badge-id'
        })).append($('<input/>', {
            type: "hidden",
            value: $this.data('name'),
            name: $this.data('name')
        })).submit();
    });

    $('.sign-used', $container).each(function() {
        var $this = $(this),
            $timer = $this.parents('.form-actions:first').find('strong > span'),
            name = $this.data('id');

        app.timers[name] = {
            left: parseInt($timer.data('left')),
            timer: setInterval(function() {
                app.timer(name, $timer);
            }, 1000)
        };
    });

    $('blockquote.footer').on('click', 'a.manage', function() {
        var $button = $(this);
        $('a.save-modal').click(function() {
            var input = $("<input/>", {
                'type': 'hidden',
                'name': 'sign',
                'value': $button.data('sign')
            });

            $('#modalForm').append($(input)).submit();
            $modal.modal('hide');
        });

        $modal.modal('show');
    });

    $.strPad = function(i,l,s) {
        var o = i.toString();
        if (!s) { s = '0'; }
        while (o.length < l) {
            o = s + o;
        }
        return o;
    };

});

app = {
    timers: {
    },
    timer : function(name, target) {
        app.timers[name].left -= 1;
        if (app.timers[name].left <= 0) {
            app.restore(name);
            return;
        }

        var m = Math.floor(app.timers[name].left / 60),
            sec = $.strPad(app.timers[name].left - 60 * m, 2, 0);
        target.text((m + ":" + sec));
    },
    restore: function(name) {
        clearInterval(app.timers[name].timer);
        window.location.href = BASE_URL;
    }
};
