function add_new_access_rule () {
    // always add at the bottom
    var count = $('#xs-access-rules tbody tr').length ;
    var text = "";
    var c = "abcdefghijklmnopqrstuvwxyz";
    var n = "0123456789" ;
    text += c.charAt(Math.floor(Math.random() * c.length));
    text += n.charAt(Math.floor(Math.random() * n.length));
    text += n.charAt(Math.floor(Math.random() * n.length));
    text += n.charAt(Math.floor(Math.random() * n.length));

    var div =   '<tr id="'+text+'__row"><td>'+count+'</td><td>' ;
    
    div = div + 'For <select id="'+text+'__source" name="f:'+text+'__source">' ;
    $('#access-rules-functionality li').each ( function () {
        t = $(this).text() ;
        div = div + '<option value="'+t+'">'+t+'</option>' ;
    }) ;
    div = div + '</select>' ;
    
    div = div + '</td><td style="">If</td>' ;
    div = div + '<td><select id="'+text+'__type" name="f:'+text+'__type"><option value="username">Username</option><option value="usertype">User is of Type</option><option value="group">User belongs to Group</option><option value="role" selected="selected">User has Role</option></select></td>' ;
    div = div + '<td>= <input type="text" id="'+text+'__what" name="f:'+text+'__what" value="Function - Intranet Editor"></td>' ;
    div = div + '<td> then <select id="'+text+'__rule" name="f:'+text+'__rule"><option value="a" selected="selected">Allow</option><option value="aae">Allow all Except</option><option value="d">Deny</option><option value="dae">Deny all Except</option></select></td>' ;
    div = div + '<td></td><td><button onclick="$(\'#'+text+'__row\').fadeOut().remove();">Delete</button></td></tr>' ;
    // alert ( div ) ;
    $div = $(div);
    $('#xs-access-rules tbody tr:last').before($div); 
    $div.hide().slideDown();
}
