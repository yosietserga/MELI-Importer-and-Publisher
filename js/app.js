$(document).foundation();

$(function(){
    $( "#profilesWrapper li" ).draggable({
        appendTo: "body",
        helper: "clone"
    });

    $( "#importerWrapper ul" ).droppable({
        accept: ":not(.ui-sortable-helper)",
        activeClass: "activeImportDrop",
        hoverClass: "hoverDrop",
        drop: function( event, ui ) {
            $( this ).find( "li:not('.placeholder')" ).remove();
            $( this ).find( "li.placeholder" ).hide();

            var tpl = '<div class="success callout" data-closable>'+
                ui.draggable.html() +
                '<button class="close-button" aria-label="Dismiss alert" type="button" onclick="clearImporter();" data-close>'+
                '<span aria-hidden="true">&times;</span>'+
                '</button>'+
                '</div>';

            $( "<li></li>" )
                .html(tpl)
                .appendTo( this );
            $( "#profileToImport" ).val( ui.draggable.data('meli_id') );
        }
    });

    $( "#exporterWrapper ul" ).droppable({
        accept: ":not(.ui-sortable-helper)",
        activeClass: "activeExportDrop",
        hoverClass: "hoverDrop",
        drop: function( event, ui ) {
            $( this ).find( "li:not('.placeholder')" ).remove();
            $( this ).find( "li.placeholder" ).hide();

            var tpl = '<div class="success callout" data-closable>'+
                ui.draggable.html() +
                '<button class="close-button" aria-label="Dismiss alert" type="button" onclick="clearExporter();" data-close>'+
                '<span aria-hidden="true">&times;</span>'+
                '</button>'+
                '</div>';

            $( "<li></li>" )
                .html(tpl)
                .appendTo( this );
            $( "#profileToExport" ).val( ui.draggable.data('meli_id') );
        }
    });
});

var downloadUploadProfile = function() {
    var profileToImport;
    var profileToExport;
    var reportToEmail;

    if ($("#reportToEmail").val().length) {
        reportToEmail = $("#reportToEmail").val();
    }

    if ($("#profileToImport").val().length) {
        profileToImport = $("#profileToImport").val();
    }

    if ($("#profileToExport").val().length) {
        profileToExport = $("#profileToExport").val();
    }

    showAlert('warning', '<h2>Procesando...</h2>');

    if (profileToImport && profileToExport) {
        $.post('meliprocesser.php?import_products=1&publish_products=1',
        {
            profileToImport:profileToImport,
            profileToExport:profileToExport,
            reportToEmail:reportToEmail
        }).done(function(resp){
            showAlert( 'success', '<h2>Actualizando...</h2><p>Espere un momento mientras se actualizan los registros</p>');
            clearWrappers();
            addActivity();
        });
    } else if (profileToImport) {
        $.post('meliprocesser.php?import_products=1',
        {
            profileToImport:profileToImport,
            reportToEmail:reportToEmail
        }).done(function(resp){
            showAlert( 'success', '<h2>Actualizando...</h2><p>Espere un momento mientras se actualizan los registros</p>');
            clearWrappers();
            addActivity();
        });
    } else if (profileToExport) {
        showAlert( 'error', '<h2>Error</h2><p>Debe agregar el perfil desde donde se van a importar los productos</p>');
    } else {
        showAlert( 'error', '<h2>Error</h2><p>Debe agregar los perfiles desde donde se van a importar los productos y hacia donde se van a publicar. El perfil hacia donde se van a publicar es opcional</p>');
    }
};

deleteProfile = function(id) {
    if (confirm('desea eliminar este perfil?')) {
        $('li[data-meli_id="' + id + '"').fadeOut(function () {
            $(this).remove();
        });
        $.post('meliprocesser.php?deleteProfile=1', {
            'id': id
        });
    }
}

clearWrappers = function() {
    $( "#exporterWrapper ul, #importerWrapper ul" ).find( "li:not('.placeholder')" ).remove();
    $( "#exporterWrapper ul, #importerWrapper ul" ).find( "li.placeholder" ).fadeIn();
};

clearImporter = function() {
    $( "#importerWrapper ul" ).find( "li:not('.placeholder')" ).remove();
    $( "#importerWrapper ul" ).find( "li.placeholder" ).fadeIn();
    $( "#profileToImport" ).val('');
};

clearExporter = function() {
    $( "#exporterWrapper ul" ).find( "li:not('.placeholder')" ).remove();
    $( "#exporterWrapper ul" ).find( "li.placeholder" ).fadeIn();
    $( "#profileToExport" ).val('');
};

showAlert = function(type, msg) {
    $('.temp').remove();
    var tpl = $(document.createElement('div'))
        .addClass('callout')
        .addClass('temp')
        .addClass(type);

    $(document.createElement('p')).html(msg).appendTo(tpl);

    $('#activitiesWrapper').prepend(tpl);
};

addActivity = function() {
    $.getJSON('meliprocesser.php?getActivities=1')
    .done(function(data){
        $('.temp').remove();
        $.each(data, function(i,item){
            var tpl ='<li>'+
                '<div class="callout primary">'+
                '<p>'+ item.description +'</p>'+
                '<small>'+ item.date_added +'</small>'+
                '</div>'+
                '</li>';
            $('#activitiesWrapper').append(tpl);
        });
    });
};