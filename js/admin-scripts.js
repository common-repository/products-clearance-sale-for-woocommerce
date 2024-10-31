// JavaScript Document

	jQuery(document).ready(function($){
		
		if(pcsfw_admin_scripts.products_list){
			
			
		
			if($('select[name="stock_status"]').length>0){
			
			var default_val = $('input[name="filter_action"]').val();
			
			$('select[name="stock_status"]').append('<option value="next-period">Next Period</option><option value="current-period">Current Period</option></select>');
			
			
			$('select[name="stock_status"]').on('change', function(){
				var val = $(this).val();
				var updated_val = default_val;
				var filter_action_class = false;
				
				if($('.next-period-btn').length>0){
					$('.next-period-btn').hide();
				}
				
				switch(val){
					case 'next-period':
						
						updated_val = 'Go';
						
						if($('.next-period-btn').length==0){
							$('<input type="button" class="button next-period-btn" value="Launch the Next Period Products">').insertAfter($('input[name="filter_action"]'));
						}
							
						if($('.next-period-btn').length>0){
							$('.next-period-btn').show();
						}
						
						filter_action_class = true;
						
					break;
					
					case 'current-period':
					
						updated_val = 'Go';		
						
						filter_action_class = true;
					
					break;
				}
				
				if(filter_action_class){
					$('input[name="filter_action"]').addClass('pcsfw-ajax-btn');
				}else{
					$('input[name="filter_action"]').removeClass('pcsfw-ajax-btn');
				}
				$('input[name="filter_action"]').val(updated_val);
				
				
			});
			
			if(pcsfw_admin_scripts.next_products_title!=''){
				$('h1.wp-heading-inline').html(pcsfw_admin_scripts.next_products_title);
				$('select[name="stock_status"]').val('next-period').trigger('change');
			}
			
			
			$('body').on('click', '.pcsfw-ajax-btn', function(event){
				
				event.preventDefault();
				console.log('A');
				if($('.next-period-btn:visible').length==0){
					document.location.href = pcsfw_admin_scripts.products_list_url;

				}else{

					var data = {
		
						action: 'wp_pcsfw_actions',
						pcsfw_nonce: pcsfw_admin_scripts.nonce,
						pcsfw_trigger: $(this).hasClass('pcsfw-ajax-btn')?'backup':'',
					}
					
					$.blockUI({ message: false });
					$.post(ajaxurl, data, function (response, code) {
						$.unblockUI();
						
						if (code == 'success') {
							
							document.location.href = pcsfw_admin_scripts.next_products_list_url;
							
						}
					});
				}
				
				
				
			});
			
			$('body').on('click', '.next-period-btn', function(event){
				
				event.preventDefault();
				
				$('.pcsfw_logger ul.pcsfw_debug_log').html('');
		
				var data = {
		
					action: 'wp_pcsfw_actions',
					pcsfw_nonce: pcsfw_admin_scripts.nonce,
					pcsfw_trigger: $(this).hasClass('next-period-btn')?'launch':'',
				}
				
				$.blockUI({ message: false });
				$.post(ajaxurl, data, function (response, code) {
					$.unblockUI();
					if (code == 'success') {
						document.location.href = pcsfw_admin_scripts.products_list_url;
					}
				});
		
				
			});
		
		}
		
		}
		
	});