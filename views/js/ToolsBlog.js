$(document).ready(function(){

    if(window.location.href.indexOf("addblog_post") > -1 || window.location.href.indexOf("addblog_category")) {

        PS_ALLOW_ACCENTED_CHARS_URL = 0;

        $('#title').on("blur",function() {
            console.log("title");
            replace_fiels($('#title').val());
        });

        $('#name_1').on("blur",function() {
            console.log("name_1");
            replace_fiels($('#name_1').val());
        });

        $('#name_2').on("blur",function() {
            console.log("name_2");
            replace_fiels($('#name_2').val());
        });
    }

    if(window.location.href.indexOf("updateblog_post") > -1 ) {

    // Check and show illustrated post image id_blog_post
    var post = $('input[name="id_blog_post"').val();

    $.ajax({
        url: '../modules/beautifulblog/img/posts/' + post + '.jpg',
        type:'HEAD',
        success: function() {
            $('.dummyfile').parent().append('<br><div class="dummyfile"><img src="../modules/beautifulblog/img/posts/' + post + '.jpg" height="200px" class="img-thumbnail"></div>');
        },
        error: function (xhr, ajaxOptions, thrownError) {
        }
    })

    $.ajax({
        type:"POST",
        url : admin_modules_link,
        async: true,
        data : {
            ajax : "1",
            controller : "AdminBlogPost",
            action : "GetKeyWords",
            token : token
        },

        dataType: "json",
        success: function(data)
        {
                console.log(data);
            $('.tagify-container').parent().append('<div id="existingtagsword"></div>');
            $('#existingtagsword').append('<style type="text/css"> #existingtagsword .box{background-color:#64E88C;border:1px solid #20CA53; border-radius:2px;color:#fff;margin:3px;padding:3px 7px 3px 9px;line-height: 26px;} </style>');

            for (item in data) {
                $('#existingtagsword').append('<span class="box">' + item + '&nbsp&nbsp<span>(' + data[item] + ')</span>&nbsp&nbsp<span><i class="icon-angle-down"></span></i></span>');
            }
            // $('textarea.tagme').tagify('serialize');
        }
    })
 }


});


function replace_fiels(nameField)
{
    if (!$('[id^="link_rewrite"]').val())
        $('[id^="link_rewrite"]').val(str2url(nameField))

    if (!$('[id^="meta_title"]').val())
        $('[id^="meta_title"]').val(nameField);

    if (!$('[id^="meta_description"]').val())
        $('[id^="meta_description"]').val(nameField);

    if (!$('[id^="long_content"]').val())
        $('[id^="long_content"]').val(nameField);
};
