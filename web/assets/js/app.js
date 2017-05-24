$(document).ready(function(){
    var locationHash = document.location.hash;

    if (locationHash) {
        $('.nav-pills a[href="'+locationHash+'"]').tab('show');
    }
    $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
        window.location.hash = e.target.hash;
    });

    $('#navbar').on('hidden.bs.collapse', function () {
        $('.navbar-header').removeClass('navbar-show');
        $('#navbar').removeClass('navbar-show').removeClass('navbar-list-show');
    }).on('show.bs.collapse', function () {
        $('.navbar-header').addClass('navbar-show');
        $('#navbar').addClass('navbar-show').addClass('navbar-list-show');
    });

    $("button.load-more").on('click', function() {
       var url = $(this).data('url');
       var page = $(this).data('page');
       var $last = $(this).parent().parent().prev().children().last();
       var $button = $(this);
       var $parent = $(this).parent();

       $(this).hide();
       $parent.append('<div class="button-load-progress"></div>').fadeIn();

        $.ajax({
            type: 'POST',
            url: url + '/' + page,
            dataType: 'json',
            success: function(data) {
                if (false === data.showLoadMoreButton) {
                    $button.parent().empty().hide();
                }
                $button.data('page', page + 1);
                $(data.html).hide().insertAfter($last).fadeIn('slow');
                $parent.find('div').remove();
                $button.show();
            },
            error: function() {
                $button.hide();
                $parent.find('div').remove();
                $parent.addClass('button-load-error').append('Oh no, something went terribly wrong :-(');
            }
        })
    });
});
