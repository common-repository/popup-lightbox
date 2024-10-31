<div class="pl inside">
    <div id="agendamento-wrapper" class="form-item form-item-textfield">
        <label for="agendamento"><?php _e("Data de Entrada", "popup-lightbox");?></label>
        <input type="text" id="agendamento" name="pl[agendamento]" value="<?php echo $custom['popup_agendamento']?>" class="form-textfield textfield datepicker" size="20" style="width: 150px;" />
        <div class="description description-textfield">
            <p><?php _e("Data exata para o popup entrar no ar", "popup-lightbox");?></p>
        </div>
    </div>
    <div id="expirar-wrapper" class="form-item form-item-textfield">
        <label for="expirar"><?php _e("Data de Expiração", "popup-lightbox");?></label>
        <input type="text" id="expirar" name="pl[expirar]" value="<?php echo $custom['popup_expirar']?>" class="form-textfield textfield datepicker" size="20" style="width: 150px;" />
        <div class="description description-textfield">
            <p><?php _e("Data exata para o popup expirar", "popup-lightbox");?></p>
        </div>
    </div>
    <p class="clear"></p>
</div>
<script type="text/javascript">

    (function($){

	    var file_frame,
	    pl_formfield = "";

	    function datepickerInit(div) {
	        
	        if (jQuery.isFunction(jQuery.fn.datetimepicker)) {
	            try {
	            	var dates = jQuery( "#agendamento, #expirar" ).datetimepicker({
	        			defaultDate: "+1 week",
	        			onClose: function(dateText, inst) {
	        				var option = this.id == "agendamento" ? "minDate" : "maxDate";
	        				var other = dates.not( this );
	        				if (other.val() != '') {
	        		            var testStartDate = this.id == "agendamento" ? new Date(dateText) : new Date(other.val());
	        		            var testEndDate = this.id != "agendamento" ? new Date(dateText) : new Date(other.val());
	        		            if (testStartDate > testEndDate)
	        		            	other.val(dateText);
	        		        }
	        		        else {
	        		        	other.val(dateText);
	        		        }
	            		},
	        			onSelect: function (selectedDateTime){
	        				var option = this.id == "agendamento" ? "minDate" : "maxDate";
	        		        var start = jQuery(this).datetimepicker('getDate');
	        		        dates.not( this ).datetimepicker('option', option, new Date(start.getTime()));
	        		    },
	    	            dateFormat: "dd/mm/yy",
	    	            altFormat: "dd/mm/yy",
	    	            timeFormat: 'hh:mm',
	                    buttonImage: "<?php echo PLIGHTBOX_RELPATH?>/images/calendar.gif",
	                    buttonImageOnly: true,
	                    buttonText: "<?php _e("Selecione a data", "popup-lightbox");?>",
	                    showOn: "button"
	        		});
				} catch (e) {}
	        }
	    }
        
    	$(document).ready(function () {
        	
    	    if ($("#post").length > 0) {

    	        $("#post").validate({
    	            errorClass: "form-error",
    	            errorPlacement: function (error, element) {
    	                error.insertBefore(element);
    	            },
    	            highlight: function (element, errorClass, validClass) {
    	                $(element).parents('.collapsible').slideDown();
    	                $("input#publish").addClass("button-primary-disabled");
    	                $("input#save-post").addClass("button-disabled");
    	                $("#save-action .ajax-loading").css("visibility", "hidden");
    	                $("#publishing-action #ajax-loading").css("visibility", "hidden");
    	            },
    	            unhighlight: function (element, errorClass, validClass) {
    	                $("input#publish, input#save-post").removeClass("button-primary-disabled").removeClass("button-disabled");
    	            },
    	        });
    	        $(".url").rules("add", {
    	            url: true
    	        });
    	        $("#select-popup-type").rules("add", {
    	            required: true
    	        });
    	    }


			$('#select-popup-type').change(function() {
				var val = $(this).val();
				
				if(val == 1){
					$('#popup-image').slideDown();
					$('#popup-video').slideUp();
				}else if(val == 2){
					$('#popup-image').slideUp();
					$('#popup-video').slideDown();
				}else{
					alert('<?php _e("Selecione um tipo válido!", "popup-lightbox") ?>');
				}
			});

			$('#pl-one-time').click(function(){
				if($(this).is(':checked')){
					$('#frequencia-wrapper').slideUp();
				}else{
					$('#frequencia-wrapper').slideDown();
				}
			});
			
			if($('#pl-one-time').is(':checked')){
				$('#frequencia-wrapper').slideUp();
			}else{
				$('#frequencia-wrapper').slideDown();
			}
    	    
    	    $('#pl-image-upload-holder').focusout(function () {
    	        if (!$(this).val())
    	            $('#'+$(this).attr('id')+'-holder-preview').html('');
    	    });

    	    $('#pl-image-upload').live('click', function (event) {

    	        event.preventDefault();

    	        pl_formfield = '#'+$(this).attr('id')+'-holder';

    	        if (file_frame) {
    	            file_frame.open();
    	            return;
    	        }

    	        file_frame = wp.media.frames.file_frame = wp.media({
    	            title: "<?php _e("Selecione uma imagem para o Popup", "popup-lightbox")?>",
    	            button: {
    	                text: "<?php _e("Use essa imagem ", "popup-lightbox")?>",
    	            },
    	            multiple: false
    	        });


    	        file_frame.on('select', function () {

    	            attachment = file_frame.state().get('selection').first().toJSON();

    	            if (typeof attachment == "object") {
    	                $(pl_formfield).val(attachment.url);

    	                if (attachment.type == 'image') {
    	                    $(pl_formfield + '-preview').html('<img src="' + attachment.url + '" width="100" />');
    	                } else {
    	                    $(pl_formfield + '-preview').html('');
    	                }
    	            } else {
    	                alert('<?php _e("Ocorreu um erro, tente enviar a imagem novamente!", "popup-lightbox")?>');
    	            }

    	        });

    	        file_frame.open();
    	    });

    	    setTimeout(function(){
        	    datepickerInit('');
    	    	$('#select-popup-type').trigger('change');
        	}, 200);
    	});
    })(jQuery);
</script>