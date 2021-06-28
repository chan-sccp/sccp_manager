
<?php
        $modules = array('test module 1 key' => array('title' => 'First Block of Defaults',
                                                        'items' => array('item1' => array('code' => 'This is the value to edit', 'title' => 'variable1'))));
?>

<div class="container-fluid">
	<div class="row">
		<div class='col-md-12'>
			<div class='fpbx-container'>
				<form autocomplete="off "class="fpbx-submit" name="frmAdmin" action="config.php?display=featurecodeadmin" method="post">
					<input type="hidden" name="action" value="save">
					<div class="display no-border">
						<div class="container-fluid">
							<h1><?php echo _("SCCP Site Defaults Administration"); ?></h1>
							<div>
								<?php echo "These default settings from chan-sccp can be overridden here. <br>If they are changed, they will not be updated by future changes in chan-sccp "?>
							</div>
							<!--Generated-->
							<?php //echo $modlines ?>
							<?php foreach($modules as $rawname => $data) { ?>
								<div class="section-title" data-for="<?php echo $rawname?>">
									<h2><i class="fa fa-minus"></i> <?php echo $data['title']?></h2>
								</div>
								<div class="section" data-id="<?php echo $rawname?>">
									<div class="element-container hidden-xs">
										<div class="row">
											<div class="form-group">
												<div class="col-md-6">
													<h4><?php echo _("Description")?></h4>
												</div>
												<div class="col-md-2">
													<h4><?php echo _("chan-sccp default")?></h4>
												</div>
												<div class="col-md-4">
													<h4><?php echo _("Actions")?></h4>
												</div>
											</div>
										</div>
									</div>
									<?php foreach($data['items'] as $item) {?>
										<div class="element-container <?php echo !empty($exten_conflict_arr[$item['code']]) ? 'has-error' : ''?>">
											<div class="row">
												<div class="form-group">
													<div class="col-md-6">
														<label class="control-label" for="<?php echo $item['feature']?>"><?php echo $item['title']?></label>
														<?php if(!empty($item['help'])) {?>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $item['feature']?>"></i>
														<?php } ?>
													</div>
													<div class="col-md-2">
														<input type="text" name="fc[<?php echo $item['module']?>][<?php echo $item['feature']?>][code]" value="<?php echo $item['code']?>" id="custom_<?php echo $item['id']?>" data-default="<?php echo $item['default']?>" placeholder="<?php echo $item['default']?>" data-custom="<?php echo $item['custom']?>" class="form-control extdisplay" <?php echo (!$item['iscustom']) ? 'readonly' : ''?> required pattern="[0-9A-D\*#]*">
													</div>
													<div class="col-md-4">
														<span class="radioset">
															<input type="checkbox" data-for="custom_<?php echo $item['id']?>" name="fc[<?php echo $item['module']?>][<?php echo $item['feature']?>][customize]" class="custom" id="usedefault_<?php echo $item['id']?>" <?php echo ($item['iscustom']) ? 'checked' : ''?>>
															<label for="usedefault_<?php echo $item['id']?>"><?php echo _("Customize")?></label>
														</span>
														<span class="radioset">
															<input type="checkbox" class="enabled" name="fc[<?php echo $item['module']?>][<?php echo $item['feature']?>][enable]" id="ena_<?php echo $item['id']?>" <?php echo ($item['isenabled']) ? 'checked' : ''?>>
															<label for="ena_<?php echo $item['id']?>"><?php echo _("Enabled")?></label>
														</span>
													</div>
												</div>
											</div>
											<div class="row">
												<div class="col-md-12">
													<span id="<?php echo $item['feature']?>-help" class="help-block fpbx-help-block"><?php echo $item['help']?></span>
												</div>
											</div>
										</div>
									<?php } ?>
								</div>
								<br/>
							<?php } ?>
							<!--END Generated-->
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
