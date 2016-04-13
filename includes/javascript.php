<?php
    define('JAVASCRIPT', true);
    require_once "common.php";
?>
$(function() {
    if (Site.ajax)
        Post.init();
        Page.init();
});
var Route = {
    action: "<?php echo fix(@$_GET['action']); ?>"
}
var Site = {
    url: '<?php echo $config->url; ?>',
    chyrp_url: '<?php echo $config->chyrp_url; ?>',
    key: '<?php if (same_origin() and logged_in()) echo token($_SERVER["REMOTE_ADDR"]); ?>',
    ajax: <?php echo($config->enable_ajax ? "true" : "false"); ?> 
}
var Post = {
    failed: false,
    init: function() {
        $(".post").last().parent().on("click", ".post_edit_link:not(.no_ajax)", function(e) {
            if (!Post.failed) {
                e.preventDefault();
                var id = $(this).attr("id").replace(/post_edit_/, "");
                Post.edit(id);
            }
        });
        $(".post").last().parent().on("click", ".post_delete_link:not(.no_ajax)", function(e) {
            if (!Post.failed) {
                e.preventDefault();

                if (confirm('<?php echo __("Are you sure you want to delete this post? If you wish to hide it, save it as a draft."); ?>')) {
                    var id = $(this).attr("id").replace(/post_delete_/, "");
                    Post.destroy(id);
                }
            }
        });
    },
    edit: function(id) {
        $("#post_" + id).loader();

        if (Site.key == "") {
            Post.panic('<?php echo __("The post cannot be edited because your web browser did not send proper credentials."); ?>');
            return;
        }

        $.post(Site.chyrp_url + "/includes/ajax.php", {
            action: "edit_post",
            id: id,
            hash: Site.key
        }, function(data) {
            if (isError(data)) {
                Post.panic();
                return;
            }

            $("#post_" + id).fadeOut("fast", function() {
                $(this).loader(true);
                $(this).replaceWith(data);
                $(window).scrollTop($("#post_edit_form_" + id).offset().top);
                $("#post_edit_form_" + id).css("opacity", 0).animate({ opacity: 1 }, function() {
                    $("#more_options_link_" + id).click(function(e) {
                        e.preventDefault();

                        if ($("#more_options_" + id).css("display") == "none") {
                            $(this).empty().append('<?php echo __("&uarr; Fewer Options"); ?>');
                            $("#more_options_" + id).slideDown("slow");
                        } else {
                            $(this).empty().append('<?php echo __("More Options &darr;"); ?>');
                            $("#more_options_" + id).slideUp("slow");
                        }
                    });
                    $("#post_edit_form_" + id).on("submit", function(e) {
                        if (!Post.failed && !!window.FormData) {
                            e.preventDefault();
                            var empties = false;
                            $(this).find("input.text, textarea").not(".optional, .more_options *").each(function() {
                                if ($(this).val() == "") {
                                    empties = true;
                                    $(this).focus();
                                    return false;
                                }
                            });

                            if (empties) {
                                alert('<?php echo __("Please complete all mandatory fields in the edit form."); ?>');
                                return;
                            }

                            $(this).loader();
                            $.ajax({
                                type: "POST",
                                url: $(this).attr("action"),
                                data: new FormData(this),
                                processData: false,
                                contentType: false,
                                dataType: "text",
                                error: Post.panic
                            }).done(Post.updated);
                        }
                    });
                    $("#post_cancel_edit_" + id).click(function(e) {
                        e.preventDefault();

                        if (!Post.failed) {
                            $("#post_edit_form_" + id).loader();
                            $.post(Site.chyrp_url + "/includes/ajax.php", {
                                action: "view_post",
                                context: Route.action,
                                id: id,
                                reason: "cancelled"
                            }, function(data) {
                                if (isError(data)) {
                                    Post.panic();
                                    return;
                                }

                                $("#post_edit_form_" + id).fadeOut("fast", function() {
                                    $(this).loader(true);
                                    $(this).replaceWith(data).fadeIn("fast");
                                });
                            }, "html").fail(Post.panic);
                        }
                    });
                });
            });
        }, "html").fail(Post.panic);
    },
    updated: function(response) {
        if (isError(response)) {
            Post.panic('<?php echo __("The post could not be updated because of an error."); ?>');
            return;
        }

        var id = Math.abs(response);

        if (Route.action != "drafts" && Route.action != "view" && $("#post_edit_form_" + id + " select#status").val() == "draft") {
            $("#post_edit_form_" + id).fadeOut("fast", function() {
                $(this).loader(true);
                alert('<?php echo __("Post has been saved as a draft."); ?>');
            })
        } else if (Route.action == "drafts" && $("#post_edit_form_" + id + " select#status").val() != "draft") {
            $("#post_edit_form_" + id).fadeOut("fast", function() {
                $(this).loader(true);
                alert('<?php echo __("Post has been published."); ?>');
            })
        } else {
            $.post(Site.chyrp_url + "/includes/ajax.php", {
                action: "view_post",
                context: Route.action,
                id: id,
                reason: "edited"
            }, function(data) {
                if (isError(data)) {
                    Post.panic();
                    return;
                }

                $("#post_edit_form_" + id).fadeOut("fast", function() {
                    $(this).loader(true);
                    $(this).replaceWith(data).fadeIn("fast");
                });
            }, "html").fail(Post.panic);
        }
    },
    destroy: function(id) {
        $("#post_" + id).loader();

        if (Site.key == "") {
            Post.panic('<?php echo __("The post cannot be deleted because your web browser did not send proper credentials."); ?>');
            return;
        }

        $.post(Site.chyrp_url + "/includes/ajax.php", {
            action: "destroy_post",
            id: id,
            hash: Site.key
        }, function(response) {
            $("#post_" + id).loader(true);

            if (isError(response)) {
                Post.panic();
                return;
            }

            $("#post_" + id).fadeOut("fast", function() {
                $(this).remove();

                if (Route.action == "view")
                    window.location = Site.url;
            });
        }, "html").fail(Post.panic);
    },
    panic: function(message) {
        message = (typeof message === "string") ? message : '<?php echo __("Oops! Something went wrong on this web page."); ?>' ;
        Post.failed = true;
        alert(message);
        $(".ajax_loading").loader(true);
        $("form.inline_edit.post_edit input[name='ajax']").remove();
    }
}
var Page = {
    failed: false,
    init: function() {
        $(".page_delete_link:not(.no_ajax)").on("click", function(e) {
            if (!Page.failed) {
                e.preventDefault();

                if (confirm('<?php echo __("Are you sure you want to delete this page? Child pages will also be deleted."); ?>')) {
                    var id = $(this).attr("id").replace(/page_delete_/, "");
                    Page.destroy(id);
                }
            }
        });
    },
    destroy: function(id) {
        $("#page_" + id).loader();

        if (Site.key == "") {
            Page.panic('<?php echo __("The page cannot be deleted because your web browser did not send proper credentials."); ?>');
            return;
        }

        $.post(Site.chyrp_url + "/includes/ajax.php", {
            action: "destroy_page",
            id: id,
            hash: Site.key
        }, function(response) {
            $("#page_" + id).loader(true);

            if (isError(response)) {
                Page.panic();
                return;
            }

            $("#page_" + id).fadeOut("fast", function() {
                $(this).remove();
                window.location = Site.url;
            });
        }, "html").fail(Page.panic);
    },
    panic: function(message) {
        message = (typeof message === "string") ? message : '<?php echo __("Oops! Something went wrong on this web page."); ?>' ;
        Page.failed = true;
        alert(message);
        $(".ajax_loading").loader(true);
    }
}
<?php $trigger->call("javascript"); ?>
