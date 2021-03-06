<?php
    define('JAVASCRIPT', true);
    require_once dirname(dirname(dirname(__FILE__))).DIRECTORY_SEPARATOR."includes".DIRECTORY_SEPARATOR."common.php";
?>
$(function() {
    toggle_all();
    toggle_options();
    validate_slug();
    validate_email();
    validate_url();
    validate_passwords();
    confirm_submit();
    Help.init();
    Write.init();
    Settings.init();
    Extend.init();
});
// Adds a master toggle to forms that have multiple checkboxes.
function toggle_all() {
    $("form[data-toggler]").each(function() {
        var all_on = true;
        var target = $(this);
        var parent = $("#" + $(this).attr("data-toggler"));
        var slaves = target.find(":checkbox");
        var master = Date.now().toString(16);

        slaves.each(function() {
            return all_on = $(this).prop("checked");
        });

        slaves.click(function(e) {
            slaves.each(function() {
                return all_on = $(this).prop("checked");
            });

            $("#" + master).prop("checked", all_on);
        });

        parent.append(
            [$("<label>").attr("for", master).text('<?php echo __("Toggle All", "admin"); ?>'),
            $("<input>", {
                "type": "checkbox",
                "name": "toggle",
                "id": master,
                "class": "checkbox"
            }).prop("checked", all_on).click(function(e) {
                slaves.prop("checked", $(this).prop("checked"));
            })]
        );
    });
}
// Toggles the visibility of #more_options based on cookie value.
function toggle_options() {
    if ($("#more_options").length) {
        if (Cookie.get("show_more_options") == "true")
            var more_options_text = '<?php echo __("&uarr; Fewer Options", "admin"); ?>';
        else
            var more_options_text = '<?php echo __("More Options &darr;", "admin"); ?>';

        $("<a>", {
            "id": "more_options_link",
            "href": "#"
        }).addClass("more_options_link").append(more_options_text).insertBefore("#more_options");

        if (Cookie.get("show_more_options") == null)
            $("#more_options").css("display", "none");

        $("#more_options_link").click(function(e) {
            e.preventDefault();

            if ($("#more_options").css("display") == "none") {
                $(this).empty().append('<?php echo __("&uarr; Fewer Options", "admin"); ?>');
                Cookie.set("show_more_options", "true", 30);
            } else {
                $(this).empty().append('<?php echo __("More Options &darr;", "admin"); ?>');
                Cookie.destroy("show_more_options");
            }
            $("#more_options").slideToggle();
        });
    }
}
// Validates slug fields.
function validate_slug() {
    $("input[name='slug']").keyup(function(e) {
        if (/^([a-z0-9\-]*)$/.test($(this).val()))
            $(this).removeClass("error");
        else
            $(this).addClass("error");
    });
}
// Validates email fields.
function validate_email() {
    $("input[type='email']").keyup(function(e) {
        if ($(this).val() != "" && !isEmail($(this).val()))
            $(this).addClass("error");
        else
            $(this).removeClass("error");
    });
}
// Validates URL fields.
function validate_url() {
    $("input[type='url']").keyup(function(e) {
        if ($(this).val() != "" && !isURL($(this).val()))
            $(this).addClass("error");
        else
            $(this).removeClass("error");
    });
}
// Tests the strength of #password1 and compares #password1 to #password2.
function validate_passwords() {
    passwords = $("input[type='password']").filter(function(index) {
        var id = $(this).attr("id");
        return (!!id) ? id.match(/password[1-2]$/) : false ;
    });

    passwords.first().keyup(function(e) {
        if (passwordStrength($(this).val()) > 99)
            $(this).addClass("strong");
        else
            $(this).removeClass("strong");
    });

    passwords.keyup(function(e) {
        if (passwords.first().val() != "" && passwords.first().val() != passwords.last().val())
            passwords.last().addClass("error");
        else
            passwords.last().removeClass("error");
    });

    passwords.parents("form").on("submit", function(e) {
        if (passwords.first().val() != passwords.last().val()) {
            e.preventDefault();
            alert('<?php echo __("Passwords do not match."); ?>');
        }
    });
}
// Asks the user to confirm form submission.
function confirm_submit() {
    $("form[data-confirm]").submit(function(e) {
        var text = $(this).attr("data-confirm") || '<?php echo __("Are you sure you want to proceed?", "admin"); ?>' ;

        if (!confirm(text.replace(/<[^>]+>/g, "")))
            e.preventDefault();
    });
}
var Route = {
    action: "<?php echo fix(@$_GET['action'], true); ?>"
}
var Site = {
    url: '<?php echo $config->url; ?>',
    chyrp_url: '<?php echo $config->chyrp_url; ?>',
    key: '<?php if (same_origin() and logged_in()) echo token($_SERVER["REMOTE_ADDR"]); ?>',
    ajax: <?php echo($config->enable_ajax ? "true" : "false"); ?> 
}
var Help = {
    init: function() {
        $(".help").on("click", function(e) {
            e.preventDefault();
            Help.show($(this).attr("href"));
        });
    },
    show: function(href) {
        $("<div>", {
            "role": "region",
        }).addClass("iframe_background").append(
            [$("<iframe>", {
                "src": href,
                "aria-label": '<?php echo __("Help", "admin"); ?>'
            }).addClass("iframe_foreground").loader(),
            $("<img>", {
                "src": Site.chyrp_url + '/admin/images/icons/close.svg',
                "alt": '<?php echo __("Close", "admin"); ?>',
                "role": 'button',
                "aria-label": '<?php echo __("Close", "admin"); ?>'
            }).addClass("iframe_close_gadget").click(function() {
                $(this).parent().remove();
            })]
        ).click(function(e) {
            if (e.target === e.currentTarget)
                $(this).remove();
        }).insertAfter("#content");
    }
}
var Write = {
    preview_support: <?php echo(file_exists(THEME_DIR.DIR."content".DIR."preview.twig") ? "true" : "false"); ?>,
    wysiwyg_editing: <?php echo($trigger->call("admin_write_wysiwyg") ? "true" : "false"); ?>,
    init: function() {
        // Insert buttons for ajax previews.
        if (Write.preview_support && !Write.wysiwyg_editing)
            $("*[data-preview]").each(function() {
                var target = $(this);

                $("label[for='" + target.attr("id") + "']").append(
                    $("<img>", {
                        "src": Site.chyrp_url + '/admin/images/icons/magnifier.svg',
                        "alt": '(<?php echo __("Preview this field", "admin"); ?>)',
                        "title": '<?php echo __("Preview this field", "admin"); ?>',
                    }).addClass("emblem preview").click(function(e) {
                        var content = target.val();
                        var filter = target.attr("data-preview");

                        if (content != "") {
                            e.preventDefault();
                            Write.show(content, filter);
                        }
                    })
                );
            });
    },
    show: function(content, filter) {
        var uid = Date.now().toString(16);

        // Build a form targeting a named iframe.
        $("<form>", {
            "id": uid,
            "action": Site.chyrp_url + "/includes/ajax.php",
            "method": "post",
            "accept-charset": "UTF-8",
            "target": uid,
            "style": "display: none;"
        }).append(
            [$("<input>", {
                "type": "hidden",
                "name": "action",
                "value": "show_preview"
            }),
            $("<input>", {
                "type": "hidden",
                "name": "filter",
                "value": filter
            }),
            $("<input>", {
                "type": "hidden",
                "name": "content",
                "value": content
            }),
            $("<input>", {
                "type": "hidden",
                "name": "hash",
                "value": Site.key
            })]
        ).insertAfter("#content");

        // Build and display the named iframe.
        $("<div>", {
            "role": "region",
        }).addClass("iframe_background").append(
            [$("<iframe>", {
                "name": uid,
                "aria-label": '<?php echo __("Preview", "admin"); ?>'
            }).addClass("iframe_foreground").loader(),
            $("<img>", {
                "src": Site.chyrp_url + '/admin/images/icons/close.svg',
                "alt": '<?php echo __("Close", "admin"); ?>',
                "role": 'button',
                "aria-label": '<?php echo __("Close", "admin"); ?>'
            }).addClass("iframe_close_gadget").click(function() {
                $(this).parent().remove();
            })]
        ).click(function(e) {
            if (e.target === e.currentTarget)
                $(this).remove();
        }).insertAfter("#content");

        // Submit the form and destroy it immediately.
        $("#" + uid).submit().remove();
    }
}
var Settings = {
    init: function() {
        $("#email_correspondence").click(function() {
            if ($(this).prop("checked") == false)
                $("#email_activation").prop("checked", false);
        });

        $("#email_activation").click(function() {
            if ($(this).prop("checked") == true)
                $("#email_correspondence").prop("checked", true);
        });

        $("form#route_settings code.syntax").on("click", function(e) {
            var name = $(e.target).text();
            var post_url = $("form#route_settings input[name='post_url']");
            var regexp = new RegExp("(^|\\/)" + escapeRegExp(name) + "([\\/]|$)", "g");

            if (regexp.test(post_url.val())) {
                post_url.val(post_url.val().replace(regexp, function(match, before, after) {
                    if (before == "/" && after == "/")
                        return "/";
                    else
                        return "";
                }));
                $(e.target).removeClass("yay");
            } else {
                if (post_url.val() == "")
                    post_url.val(name);
                else
                    post_url.val(post_url.val().replace(/(\/?)?$/, "\/" + name));

                $(e.target).addClass("yay");
            }
        }).css("cursor", "pointer");

        $("form#route_settings input[name='post_url']").on("keyup", function(e) {
            $("form#route_settings code.syntax").each(function(){
                regexp = new RegExp("(/?|^)" + $(this).text() + "(/?|$)", "g");

                if ($(e.target).val().match(regexp))
                    $(this).addClass("yay");
                else
                    $(this).removeClass("yay");
            });
        }).trigger("keyup");
    }
}
var Extend = {
    init: function() {
        // Hide the confirmation checkbox and use a modal instead.
        $(".module_disabler_confirm").hide();
        $(".module_disabler").on("submit.confirm", Extend.confirm);
    },
    confirm: function(e) {
        e.preventDefault();

        var id = $(e.target).parents("li.module").attr("id");
        var name = (!!id) ? id.replace(/^module_/, "") : "" ;
        var text = $('label[for="confirm_' + name + '"]').html();

        // Display the modal if the text was found, and set the checkbox to the response.
        if (!!text)
            $('#confirm_' + name).prop("checked", confirm(text.replace(/<[^>]+>/g, "")));

        // Disable this handler and resubmit the form with the checkbox set accordingly.
        $(e.target).off("submit.confirm").submit();
    }
}
<?php $trigger->call("admin_javascript"); ?>
