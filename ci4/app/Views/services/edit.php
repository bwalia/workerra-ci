<?php 
    header('Content-Type: text/html');
    require_once(APPPATH . 'Views/common/edit-title.php');
    $blocks_list = getResultArray("blocks_list", ["uuid_linked_table" => @$service->uuid]);
    $domains = getResultArray("domains", ["sid" => @$service->uuid]);
    $templates = getResultArray("templates", ['module_name' => 'services']);
    $uri = service('uri');
    $uriSegment = $uri->getSegment(3);
?>
<style>
    .hidden {
        display: none;
    }
</style>
<div class="white_card_body">
    <div class="card-body">
        <form id="addservice" method="post" action="/services/update" enctype="multipart/form-data">
            <div class="row">
                <div class="col-xs-12 col-md-12">
                    <nav>
                        <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                            <a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">Service Detail</a>
                            <a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">Service Secrets</a>
                            <a class="nav-item nav-link" id="nav-domains-tab" data-toggle="tab" href="#nav-domains" role="tab" aria-controls="nav-domains" aria-selected="false">Domains</a>
                            <a class="nav-item nav-link" id="nav-tags-tab" data-toggle="tab" href="#nav-tags" role="tab" aria-controls="nav-tags" aria-selected="false">Tags</a>
                            <a class="nav-item nav-link" id="nav-steps-tab" data-toggle="tab" href="#nav-steps" role="tab" aria-controls="nav-steps" aria-selected="false">Service Workflows</a>
                        </div>
                    </nav>
                    <div class="tab-content py-3 px-3 px-sm-0 col-md-12" id="nav-tabContent">
                        <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                            <div class="form-row">
                                <div class="form-group required col-md-6">
                                    <label for="inputEmail4">Name</label>
                                    <input autocomplete="off" autocomplete="off" type="text" class="form-control required" id="name" name="name" placeholder="" value="<?= @$service->name ?>">

                                    <input type="hidden" class="form-control" name="id" placeholder="" value="<?= @$service->uuid ?>" />
                                </div>
                                <div class="form-group required col-md-6">
                                    <label for="inputState">Choose User</label>
                                    <select id="uuid" name="uuid" class="form-control required">
                                        <option value="" selected="">--Select--</option>
                                        <?php foreach ($users as $row) : ?>
                                            <option value="<?= $row['uuid']; ?>" <?= ($row['uuid'] == @$service->user_uuid) ? 'selected' : '' ?>>
                                                <?= $row['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>


                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="inputState">Choose Category</label>
                                    <select id="cid" name="cid" class="form-control">
                                        <option value="" selected="">--Select--</option>
                                        <?php foreach ($category as $row) : ?>
                                            <option value="<?= $row['id']; ?>" <?= ($row['id'] == @$service->cid) ? 'selected' : '' ?>>
                                                <?= $row['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="form-group col-md-6">
                                    <label for="inputState">Choose Tenant</label>
                                    <select id="tid" name="tid" class="form-control">
                                        <option value="" selected="">--Select--</option>
                                        <?php foreach ($tenants as $row) : ?>
                                            <option value="<?= $row['id']; ?>" <?= ($row['id'] == @$service->tid) ? 'selected' : '' ?>>
                                                <?= $row['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>


                            <div class="form-row">
                                <div class="form-group required col-md-6">
                                    <label for="inputPassword4">Code</label>
                                    <input autocomplete="off" autocomplete="off" type="text" class="form-control required" id="code" name="code" placeholder="" value="<?= @$service->code ?>">
                                </div>
                                <div class="form-group required col-md-6">
                                    <label for="inputPassword4">Description</label>
                                    <textarea class="form-control required" id="notes" name="notes" rows="16" placeholder=""><?= @$service->notes ?></textarea>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="inputPassword4">Clone URL</label>
                                    <input type="text" class="form-control" id="clone-url" name="clone-url" placeholder="" value="<?= @$service->link ?>">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="inputEmail4">Environment Tags</label>

                                    <select id="my-select2" data-select2-tags="true" name="env_tags[]" multiple="multiple" class="form-control select2">
                                        <?php
                                        if (!empty($service->env_tags)) {
                                            $arr = explode(',', $service->env_tags);
                                            foreach ($arr as $row) : ?>
                                                <option value="<?= $row; ?>" selected="selected">
                                                    <?= $row; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6 required">
                                    <label for="service_type">Service Type</label>
                                    <select class="custom-select required" id="service_type" name="service_type">
                                        <option selected>Please Select the Service Type</option>
                                        <option value="workflows" <?=@$service->service_type == 'workflows' ? 'selected' : ''?> >Run Workflows</option>
                                        <option value="marketing" <?=@$service->service_type == 'marketing' ? 'selected' : ''?>>Marketing</option>
                                    </select>
                                </div>
                            </div>
                            <?php $secret_values_templates['secret_template_id'] = isset($secret_values_templates['secret_template_id']) ? @$secret_values_templates['secret_template_id'] : []; ?>
                            <div class="service-template-wrapper <?php echo @$service->service_type == 'workflows' ? 'show' : 'hidden'; ?>" id="service-template-wrapper">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="inputPassword4">Secret Template</label>
                                        <select id="secret_template" name="secret_template[]" class="form-control select2 multiple" multiple>
                                            <!-- <option value="" selected="">--Select--</option> -->
                                            <?php foreach ($templates as $template) : ?>
                                                <option value="<?= $template['uuid']; ?>" <?= (in_array($template['uuid'], @$secret_values_templates['secret_template_id'])) ? 'selected' : '' ?>>
                                                    <?= $template['code']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="inputPassword4">Values Template</label>
                                        <select id="values_template" name="values_template" class="form-control">
                                            <option value="" selected="">--Select--</option>
                                            <?php foreach ($templates as $template) : ?>
                                                <option value="<?= $template['uuid']; ?>" <?= ($template['uuid'] == @$secret_values_templates['values_template_id']) ? 'selected' : '' ?>>
                                                    <?= $template['code']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-6" id="secretKeysName"></div>
                                    <div class="form-group col-md-6" id="valuesKeysName"></div>
                                </div>
                            </div>
                            <div class="marketing-wrapper <?php echo @$service->service_type == 'marketing' ? 'show' : 'hidden'; ?>" id="marketing-wrapper">
                                <div class="form-row">
                                    <div class="form-group col-md-6">
                                        <label for="inputPassword4">Email Template</label>
                                        <select id="email_marketing_template" name="email_marketing_template" class="form-control">
                                            <option value="" selected="">--Select--</option>
                                            <?php foreach ($templates as $template) : ?>
                                                <option value="<?= $template['uuid']; ?>" <?= ($template['uuid'] == @$secret_values_templates['marketing_template_id']) ? 'selected' : '' ?>>
                                                    <?= $template['code']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="inputAddress">Logo Upload</label>

                                    <span class="all-media-image-files">
                                        <?php if (!empty(@$service->image_logo)) { ?>
                                            <img class="img-rounded" src="<?= @$service->image_logo; ?>" width="100px">
                                            <a href="/services/rmimg/image_logo/<?= @$service->uuid ?>" onclick="return confirm('Are you sure?')" class="btn btn-danger"><i class="fa fa-trash"></i></a>
                                        <?php } ?>
                                    </span>
                                    <div class="uplogInrDiv" id="drop_file_doc_zone">
                                        <input type="file" name="file" class="form-control fileUpload  form-control-lg" id="file">
                                        <div class="uploadBlkInr">
                                            <div class="uplogImg">
                                                <img src="/assets/img/fileupload.png" />
                                            </div>
                                            <div class="uploadFileCnt">
                                                <p>
                                                    <a href="#">Upload a file </a> file chosen or drag
                                                    and drop
                                                </p>
                                                <p>
                                                    <span>Video, PNG, JPG, GIF up to 10MB</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>


                                </div>
                                <div class="form-group col-md-6">
                                    <label for="inputAddress">Brand Upload</label>
                                    <span class="all-media-image-files2 media-files">
                                        <?php if (!empty(@$service->image_brand)) { ?>
                                            <img src="<?= @$service->image_brand; ?>" width="100px">
                                            <a href="/services/rmimg/image_brand/<?= @$service->uuid ?>" onclick="return confirm('Are you sure?')" class="btn btn-danger"><i class="fa fa-trash"></i></a>
                                        <?php } ?>
                                    </span>
                                    <div class="uplogInrDiv " id="drop_file_doc_zone2">

                                        <input type="file" name="file2" class="fileUpload2" id="file2">
                                        <div class="uploadBlkInr">
                                            <div class="uplogImg">
                                                <img src="/assets/img/fileupload.png" />
                                            </div>
                                            <div class="uploadFileCnt">
                                                <p>
                                                    <a href="#">Upload a file </a> file chosen or drag
                                                    and drop
                                                </p>
                                                <p>
                                                    <span>Video, PNG, JPG, GIF up to 10MB</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                            <div class="form-row">
                                <?php /*
                     for($jak_i=0; $jak_i<count($defaultSecret); $jak_i++){
                         $new_id = $jak_i + 1;
                         
                         if(empty($default_secrets_services[$jak_i]['secrets_default_value']))
                             $default_secrets_services[$jak_i]['secrets_default_value'] = '';
                 ?>
                 <div class="form-row col-md-12">
                     <div class="form-group col-md-6">
                         <label for="inputEmail4">Secret Key</label>
                         <input autocomplete="off" autocomplete="off" type="text" class="form-control" id="default_key_name_<?php echo $new_id; ?>" name="default_key_name[]" readonly placeholder="" value="<?=$defaultSecret[$jak_i]['secrets_default_key'] ?>">
                     </div>
                     <div class="form-group col-md-6">
                         <label for="inputEmail4">Secret Value</label>
                         <input autocomplete="off" type="text" class="form-control" id="default_key_value_<?php echo $new_id; ?>" name="default_key_value[]" placeholder="" value="<?=$default_secrets_services[$jak_i]['secrets_default_value'] ?>">
                     </div>
                 </div>
                 <?php
                     }
                 */ ?>
                            </div>

                            <?php
                            if (count($secret_services) > 0) {
                            ?>
                                <div class="form-row addresscontainer" id="addresscontainer">
                                    <?php
                                    for ($jak_i = 0; $jak_i < count($secret_services); $jak_i++) {
                                        $new_id = $jak_i + 1;
                                    ?>
                                        <div class="form-row col-md-12 secret-row-container" id="office_address_<?php echo $new_id; ?>" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 20px;">
                                            <div class="form-group col-md-3">
                                                <label for="inputEmail4">
                                                    <i class="fa fa-key"></i> Secret Key
                                                </label>
                                                <input autocomplete="off" type="text" class="form-control" id="key_name_<?php echo $new_id; ?>" name="key_name[]" placeholder="e.g., API_KEY" value="<?= $secret_services[$jak_i]['key_name'] ?>">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="inputEmail4">
                                                    <i class="fa fa-lock"></i> Secret Value
                                                </label>
                                                <input autocomplete="off" type="text" class="form-control" id="key_value_<?php echo $new_id; ?>" name="key_value[]" placeholder="Enter secret value" value="<?= (!empty($_SESSION['role']) && $_SESSION['role'] == 1) ? $secret_services[$jak_i]['key_value'] : '********' ?>">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="my-select2_<?php echo $new_id; ?>">
                                                    <i class="fa fa-server"></i> Environment
                                                </label>
                                                <select id="my-select2_<?php echo $new_id; ?>" data-select2-tags="true" name="secret_tags[]" class="form-control select2">
                                                    <option value="" >--Select--</option>
                                                    <?php
                                                    if (isset($secret_services[$jak_i]['secret_tags'])) {
                                                        ?>
                                                            <option value="<?= $secret_services[$jak_i]['secret_tags']; ?>" selected="selected">
                                                                <?= $secret_services[$jak_i]['secret_tags']; ?>
                                                            </option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="secret_domains_<?php echo $new_id; ?>">
                                                    <i class="fa fa-globe"></i> Domains
                                                </label>
                                                <select id="secret_domains_<?php echo $new_id; ?>" name="secret_domains_<?php echo $new_id; ?>[]" class="form-control select2-domains" multiple="multiple" data-placeholder="Select domains...">
                                                    <?php foreach ($all_domains as $domain) : ?>
                                                        <option value="<?= $domain['uuid']; ?>">
                                                            <?= $domain['name']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <small class="form-text text-muted">Where this secret is used</small>
                                            </div>
                                            <input type="hidden" class="form-control" name="secret_uuid[]" placeholder="" value="<?= @$secret_services[$jak_i]['uuid'] ?>" />
                                            <?php
                                            if ($jak_i == 0) {
                                            ?>
                                                <div class="form-group col-md-1 change d-flex">
                                                    <button class="btn btn-primary bootstrap-touchspin-up add " type="button" style="max-height: 35px;margin-top: 28px;margin-left: 10px;">+</button>
                                                    <button class="btn btn-info bootstrap-touchspin-up deleteaddress" data-type="secret_services" data-id="<?= $secret_services[$jak_i]['id'] ?>" id="deleteRow" type="button" style="max-height: 35px;margin-top: 28px;margin-left: 10px;">-</button>
                                                </div>
                                            <?php
                                            } else {
                                            ?>
                                                <div class="form-group col-md-1 change">
                                                    <button class="btn btn-info bootstrap-touchspin-up deleteaddress" data-type="secret_services" data-id="<?= $secret_services[$jak_i]['id'] ?>" id="deleteRow" type="button" style="max-height: 35px;margin-top: 28px;margin-left: 10px;">-</button>
                                                </div>
                                            <?php
                                            }
                                            ?>
                                            <div class="form-group col-md-1 change">
                                                <button 
                                                    class="btn btn-info bootstrap-touchspin-up clone-row" 
                                                    data-type="secret_services" 
                                                    data-uuid="<?= $secret_services[$jak_i]['uuid'] ?>" 
                                                    data-id="office_address_<?php echo $new_id; ?>"
                                                    data-key="<?php echo $new_id; ?>"
                                                    id="cloneRow" 
                                                    type="button" 
                                                    style="max-height: 35px;margin-top: 28px;margin-left: 10px;"
                                                >
                                                    <i class="fas fa-clone"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>

                                <input type="hidden" value="<?php echo count($secret_services); ?>" id="total_secret_services" name="total_secret_services">

                            <?php
                            } else {
                            ?>
                                <div class="form-row col-md-12" id="office_address_1" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 20px; margin-bottom: 20px;">
                                    <div class="form-group col-md-3">
                                        <label for="inputEmail4">
                                            <i class="fa fa-key"></i> Secret Key
                                        </label>
                                        <input autocomplete="off" type="text" class="form-control" id="key_name_1" name="key_name[]" placeholder="e.g., API_KEY" value="">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="inputEmail4">
                                            <i class="fa fa-lock"></i> Secret Value
                                        </label>
                                        <input autocomplete="off" type="text" class="form-control" id="key_value_1" name="key_value[]" placeholder="Enter secret value" value="">
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="my-select2_0">
                                            <i class="fa fa-server"></i> Environment
                                        </label>
                                        <select id="my-select2_0" data-select2-tags="true" name="secret_tags[]" class="form-control select2">
                                            <option value="" >--Select--</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label for="secret_domains_1">
                                            <i class="fa fa-globe"></i> Domains
                                        </label>
                                        <select id="secret_domains_1" name="secret_domains_1[]" class="form-control select2-domains" multiple="multiple" data-placeholder="Select domains...">
                                            <?php foreach ($all_domains as $domain) : ?>
                                                <option value="<?= $domain['uuid']; ?>">
                                                    <?= $domain['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">Where this secret is used</small>
                                    </div>
                                    <div class="form-group col-md-1 change">
                                        <button class="btn btn-primary bootstrap-touchspin-up add" type="button" style="max-height: 35px;margin-top: 28px;margin-left: 10px;">+</button>
                                    </div>
                                    <div class="form-group col-md-1 change">
                                        <button
                                            class="btn btn-info bootstrap-touchspin-up clone-row"
                                            data-type="secret_services"
                                            data-uuid=""
                                            data-id="office_address_1"
                                            data-key="1"
                                            id="cloneRow"
                                            type="button"
                                            style="max-height: 35px;margin-top: 28px;margin-left: 10px;"
                                        >
                                            <i class="fas fa-clone"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="form-row addresscontainer">

                                </div>
                                <input type="hidden" value="1" id="total_secret_services" name="total_secret_services">
                            <?php
                            }
                            ?>

                            <style>
                                .secret-row-container {
                                    transition: background-color 0.2s;
                                }
                                .secret-row-container:hover {
                                    background-color: #f9fafb;
                                }
                                .select2-domains .select2-selection__choice {
                                    background-color: #667eea !important;
                                    color: white !important;
                                    border: none !important;
                                    padding: 4px 8px !important;
                                    border-radius: 12px !important;
                                }
                                .select2-domains .select2-selection__choice__remove {
                                    color: white !important;
                                    margin-right: 5px !important;
                                }
                            </style>

                            <script>
                                // Initialize Select2 for domain dropdowns in secrets
                                $(document).ready(function() {
                                    // Initialize existing domain selects
                                    $('.select2-domains').select2({
                                        placeholder: 'Select domains...',
                                        allowClear: true,
                                        width: '100%'
                                    });

                                    // When adding new secret rows, initialize Select2 for the new domain dropdown
                                    $(document).on('click', '.add', function() {
                                        setTimeout(function() {
                                            $('.select2-domains').not('.select2-hidden-accessible').select2({
                                                placeholder: 'Select domains...',
                                                allowClear: true,
                                                width: '100%'
                                            });
                                        }, 100);
                                    });

                                    // When cloning secret rows, reinitialize Select2
                                    $(document).on('click', '.clone-row', function() {
                                        setTimeout(function() {
                                            $('.select2-domains').select2('destroy');
                                            $('.select2-domains').select2({
                                                placeholder: 'Select domains...',
                                                allowClear: true,
                                                width: '100%'
                                            });
                                        }, 100);
                                    });
                                });
                            </script>
                        </div>

                        <div class="tab-pane fade" id="nav-steps" role="tabpanel" aria-labelledby="nav-steps-tab">
                            <?php
                            if (count($blocks_list) > 0) {
                            ?>
                                <div class="form-row blocks_html">
                                    <?php
                                    for ($jak_i = 0; $jak_i < count($blocks_list); $jak_i++) {
                                        $new_id = $jak_i + 1;
                                    ?>
                                        <div class="form-row col-md-12 each-row each-block" style="margin-bottom:30px;" id="blocks_html_<?php echo $new_id; ?>">
                                            <div class="form-group col-md-6">
                                                <label for="inputEmail4">Code</label>
                                                <input autocomplete="off" type="text" class="form-control blocks_code" id="blocks_code<?php echo $new_id; ?>" name="blocks_code[]" placeholder="" value="<?= $blocks_list[$jak_i]['code'] ?>"><br>

                                                <label for="inputEmail4">Title</label>
                                                <input autocomplete="off" type="text" class="form-control" id="blocks_title<?php echo $new_id; ?>" name="blocks_title[]" placeholder="" value="<?= $blocks_list[$jak_i]['title'] ?>"><br>

                                                <label for="inputEmail4">Sort</label>
                                                <input autocomplete="off" type="number" class="form-control" name="sort[]" placeholder="" value="<?= $blocks_list[$jak_i]['sort'] ?>">

                                                <label for="inputEmail4">Type</label>
                                                <select name="type[]" id="text_type" class="form-control text_type">
                                                    <option value="database" <?php if ($blocks_list[$jak_i]['type'] == 'database')
                                                                                    echo "selected"; ?>>Database</option>
                                                    <option value="nginx" <?php if ($blocks_list[$jak_i]['type'] == 'nginx')
                                                                                echo "selected"; ?>>Nginx</option>
                                                    <option value="dns" <?php if ($blocks_list[$jak_i]['type'] == 'dns')
                                                                            echo "selected"; ?>>DNS</option>
                                                    <option value="varnish" <?php if ($blocks_list[$jak_i]['type'] == 'varnish')
                                                                                echo "selected"; ?>>Varnish</option>
                                                    <option value="secrets" <?php if ($blocks_list[$jak_i]['type'] == 'secrets')
                                                                                echo "selected"; ?>>Secrets</option>
                                                    <option value="bash" <?php if ($blocks_list[$jak_i]['type'] == 'bash')
                                                                                echo "selected"; ?>>Bash</option>
                                                </select>
                                            </div>

                                            <input type="hidden" class="hidden_type_value" value="<?= $blocks_list[$jak_i]['type'] ?>">
                                            <input type="hidden" class="hidden_blocks_text_value" value="<?= $blocks_list[$jak_i]['text'] ?>">

                                            <div class="form-group col-md-5 textarea-block">
                                                <label class="textarea_label" for="inputEmail4">
                                                    <?php echo @$type[$blocks_list[$jak_i]['type']]; ?>
                                                </label>

                                                <textarea class="form-control blocks_text <?php if ($blocks_list[$jak_i]['type'] == 'WYSIWYG') {
                                                                                                echo "myClassName";
                                                                                            } else {
                                                                                                echo "textarea-height";
                                                                                            } ?>" id="blocks_text<?php echo $new_id; ?>" name="blocks_text[]" rows="12"><?= $blocks_list[$jak_i]['text'] ?></textarea>
                                            </div>
                                            <input type="hidden" value="<?= $blocks_list[$jak_i]['id'] ?>" id="blocks_id" name="blocks_id[]">

                                            <div class="form-group col-md-1 change">
                                                <button class="btn btn-info bootstrap-touchspin-up deleteaddress" id="deleteRow" type="button" data-type="service_step" data-id="<?= $blocks_list[$jak_i]['id'] ?>" style="max-height: 35px;margin-top: 38px;margin-left: 10px;margin-bottom:10px;">-</button>
                                                <br>
                                                <a href="#" class="tooltip-class" style="margin-left: 23px;" data-toggle="tooltip" title="<?= @$data_type_format[$blocks_list[$jak_i]['type']]; ?>"><i class="fa fa-info-circle"></i></a>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                    ?>
                                </div>

                                <input type="hidden" value="<?php echo count($blocks_list); ?>" id="total_blocks" name="total_blocks" />

                            <?php
                            } else {
                            ?>
                                <div class="form-row each-block" style="margin-bottom:30px;" id="blocks_html_1">
                                    <div class="form-group col-md-6">
                                        <label for="inputEmail4">Code</label>
                                        <input autocomplete="off" type="text" class="form-control blocks_code" id="first_name_1" name="blocks_code[]" placeholder="" value="">

                                        <label for="inputEmail4">Title</label>
                                        <input autocomplete="off" type="text" class="form-control" id="surname" name="blocks_title[]" placeholder="" value="">
                                        <label for="inputEmail4">Sort</label>
                                        <input autocomplete="off" type="number" class="form-control" name="sort[]" placeholder="" value="">

                                        <label for="inputEmail4">Type</label>
                                        <select name="type[]" id="text_type" class="form-control text_type">
                                            <option value="database">Database</option>
                                            <option value="nginx">Nginx</option>
                                            <option value="dns">DNS</option>
                                            <option value="varnish">Varnish</option>
                                            <option value="secrets">Secrets</option>
                                            <option value="bash">Bash</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-5 textarea-block">
                                        <label class="textarea_label" for="inputEmail4">Text</label>
                                        <textarea class="form-control textarea-height blocks_text" id="ck-content" name="blocks_text[]" rows="12"></textarea>
                                    </div>
                                </div>
                                <input type="hidden" value="0" id="contact_id" name="contact_id">
                                <div class="form-row blocks_html">
                                </div>
                                <input type="hidden" value="1" id="total_blocks" name="total_blocks">
                            <?php
                            }
                            ?>

                            <div class="form-group">
                                <button class="btn btn-primary add_step" type="button" style="float:right;margin-right: 120px;">Add Steps</button><br><br>
                            </div>

                        </div>

                        <div class="tab-pane fade" id="nav-domains" role="tabpanel" aria-labelledby="nav-domains-tab">
                            <style>
                                .domains-dual-panel-container {
                                    display: grid;
                                    grid-template-columns: 1fr auto 1fr;
                                    gap: 24px;
                                    margin: 20px 0;
                                }
                                .domains-panel {
                                    background: white;
                                    border-radius: 12px;
                                    padding: 20px;
                                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                    min-height: 400px;
                                }
                                .domains-panel-header {
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    margin-bottom: 16px;
                                    padding-bottom: 12px;
                                    border-bottom: 2px solid #e5e7eb;
                                }
                                .domains-panel-title {
                                    font-size: 1rem;
                                    font-weight: 700;
                                    color: #1f2937;
                                }
                                .domains-panel-count {
                                    background: #667eea;
                                    color: white;
                                    padding: 2px 10px;
                                    border-radius: 12px;
                                    font-size: 0.75rem;
                                }
                                .domains-panel-search {
                                    width: 100%;
                                    padding: 8px 12px;
                                    border: 1px solid #e5e7eb;
                                    border-radius: 6px;
                                    margin-bottom: 12px;
                                }
                                .domains-list {
                                    max-height: 450px;
                                    overflow-y: auto;
                                }
                                .domain-item {
                                    padding: 12px;
                                    margin-bottom: 8px;
                                    border: 2px solid #e5e7eb;
                                    border-radius: 8px;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    display: flex;
                                    align-items: center;
                                    gap: 10px;
                                }
                                .domain-item:hover {
                                    border-color: #667eea;
                                    background: #f9fafb;
                                }
                                .domain-item.selected {
                                    border-color: #10b981;
                                    background: #d1fae5;
                                }
                                .domain-icon {
                                    width: 36px;
                                    height: 36px;
                                    border-radius: 8px;
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: white;
                                    font-weight: 600;
                                }
                                .domain-info {
                                    flex-grow: 1;
                                }
                                .domain-name {
                                    font-weight: 600;
                                    color: #1f2937;
                                }
                                .domain-meta {
                                    font-size: 0.75rem;
                                    color: #6b7280;
                                }
                                .domains-transfer-controls {
                                    display: flex;
                                    flex-direction: column;
                                    gap: 12px;
                                    justify-content: center;
                                }
                                .domains-transfer-btn {
                                    padding: 12px 20px;
                                    border-radius: 8px;
                                    border: 2px solid #667eea;
                                    background: #667eea;
                                    color: white;
                                    font-weight: 600;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                }
                                .domains-transfer-btn:hover:not(:disabled) {
                                    background: #5568d3;
                                    transform: scale(1.05);
                                }
                                .domains-transfer-btn:disabled {
                                    background: #e5e7eb;
                                    border-color: #e5e7eb;
                                    color: #9ca3af;
                                    cursor: not-allowed;
                                }
                                .domains-empty-state {
                                    text-align: center;
                                    padding: 40px 20px;
                                    color: #9ca3af;
                                }
                            </style>

                            <?php
                            $assignedDomainUUIDs = array_column($serviceDomains, 'domain_uuid');
                            ?>

                            <div class="domains-dual-panel-container">
                                <!-- Available Domains Panel -->
                                <div class="domains-panel">
                                    <div class="domains-panel-header">
                                        <div class="domains-panel-title">
                                            <i class="fa fa-globe"></i> Available Domains
                                            <span class="domains-panel-count" id="availableDomainsCount">0</span>
                                        </div>
                                    </div>
                                    <input type="text" class="domains-panel-search" id="searchAvailableDomains" placeholder="Search domains...">
                                    <div class="domains-list" id="availableDomainsList">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                </div>

                                <!-- Transfer Controls -->
                                <div class="domains-transfer-controls">
                                    <button type="button" class="domains-transfer-btn" id="assignDomainsBtn" onclick="assignSelectedDomains()" disabled>
                                        <i class="fa fa-arrow-right"></i>
                                    </button>
                                    <button type="button" class="domains-transfer-btn" onclick="assignAllDomains()">
                                        <i class="fa fa-angle-double-right"></i>
                                    </button>
                                    <button type="button" class="domains-transfer-btn" onclick="removeAllDomains()">
                                        <i class="fa fa-angle-double-left"></i>
                                    </button>
                                    <button type="button" class="domains-transfer-btn" id="removeDomainsBtn" onclick="removeSelectedDomains()" disabled>
                                        <i class="fa fa-arrow-left"></i>
                                    </button>
                                </div>

                                <!-- Assigned Domains Panel -->
                                <div class="domains-panel">
                                    <div class="domains-panel-header">
                                        <div class="domains-panel-title">
                                            <i class="fa fa-check-circle"></i> Assigned Domains
                                            <span class="domains-panel-count" id="assignedDomainsCount">0</span>
                                        </div>
                                    </div>
                                    <input type="text" class="domains-panel-search" id="searchAssignedDomains" placeholder="Search assigned...">
                                    <div class="domains-list" id="assignedDomainsList">
                                        <!-- Populated by JavaScript -->
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden inputs for form submission -->
                            <div id="domainsHiddenInputs"></div>

                            <script>
                            // All domains data
                            const allDomains = <?= json_encode($all_domains); ?>;
                            const assignedDomainUUIDs = <?= json_encode($assignedDomainUUIDs); ?>;

                            let availableDomains = [];
                            let assignedDomains = [];
                            let selectedAvailableDomains = [];
                            let selectedAssignedDomains = [];

                            // Initialize on page load
                            $(document).ready(function() {
                                initializeDomainLists();
                                setupDomainSearchFilters();
                            });

                            function initializeDomainLists() {
                                availableDomains = allDomains.filter(d => !assignedDomainUUIDs.includes(d.uuid));
                                assignedDomains = allDomains.filter(d => assignedDomainUUIDs.includes(d.uuid));

                                renderAvailableDomains();
                                renderAssignedDomains();
                                updateDomainCounts();
                                updateDomainHiddenInputs();
                            }

                            function renderAvailableDomains(filter = '') {
                                const container = $('#availableDomainsList');
                                container.empty();

                                const filtered = availableDomains.filter(d =>
                                    d.name.toLowerCase().includes(filter.toLowerCase())
                                );

                                if (filtered.length === 0) {
                                    container.html('<div class="domains-empty-state"><i class="fa fa-inbox fa-3x"></i><p>No domains available</p></div>');
                                    return;
                                }

                                filtered.forEach(domain => {
                                    const initial = domain.name.charAt(0).toUpperCase();
                                    const isSelected = selectedAvailableDomains.includes(domain.uuid);
                                    const item = $(`
                                        <div class="domain-item ${isSelected ? 'selected' : ''}" data-uuid="${domain.uuid}" onclick="toggleAvailableDomain('${domain.uuid}')">
                                            <div class="domain-icon">${initial}</div>
                                            <div class="domain-info">
                                                <div class="domain-name">${domain.name}</div>
                                                <div class="domain-meta">Click to select</div>
                                            </div>
                                        </div>
                                    `);
                                    container.append(item);
                                });
                            }

                            function renderAssignedDomains(filter = '') {
                                const container = $('#assignedDomainsList');
                                container.empty();

                                const filtered = assignedDomains.filter(d =>
                                    d.name.toLowerCase().includes(filter.toLowerCase())
                                );

                                if (filtered.length === 0) {
                                    container.html('<div class="domains-empty-state"><i class="fa fa-inbox fa-3x"></i><p>No domains assigned</p></div>');
                                    return;
                                }

                                filtered.forEach(domain => {
                                    const initial = domain.name.charAt(0).toUpperCase();
                                    const isSelected = selectedAssignedDomains.includes(domain.uuid);
                                    const item = $(`
                                        <div class="domain-item ${isSelected ? 'selected' : ''}" data-uuid="${domain.uuid}" onclick="toggleAssignedDomain('${domain.uuid}')">
                                            <div class="domain-icon">${initial}</div>
                                            <div class="domain-info">
                                                <div class="domain-name">${domain.name}</div>
                                                <div class="domain-meta">Click to select</div>
                                            </div>
                                        </div>
                                    `);
                                    container.append(item);
                                });
                            }

                            function toggleAvailableDomain(uuid) {
                                const index = selectedAvailableDomains.indexOf(uuid);
                                if (index > -1) {
                                    selectedAvailableDomains.splice(index, 1);
                                } else {
                                    selectedAvailableDomains.push(uuid);
                                }
                                renderAvailableDomains($('#searchAvailableDomains').val());
                                updateDomainButtons();
                            }

                            function toggleAssignedDomain(uuid) {
                                const index = selectedAssignedDomains.indexOf(uuid);
                                if (index > -1) {
                                    selectedAssignedDomains.splice(index, 1);
                                } else {
                                    selectedAssignedDomains.push(uuid);
                                }
                                renderAssignedDomains($('#searchAssignedDomains').val());
                                updateDomainButtons();
                            }

                            function assignSelectedDomains() {
                                selectedAvailableDomains.forEach(uuid => {
                                    const domain = availableDomains.find(d => d.uuid === uuid);
                                    if (domain) {
                                        assignedDomains.push(domain);
                                        availableDomains = availableDomains.filter(d => d.uuid !== uuid);
                                    }
                                });

                                selectedAvailableDomains = [];
                                renderAvailableDomains($('#searchAvailableDomains').val());
                                renderAssignedDomains($('#searchAssignedDomains').val());
                                updateDomainCounts();
                                updateDomainButtons();
                                updateDomainHiddenInputs();
                            }

                            function removeSelectedDomains() {
                                selectedAssignedDomains.forEach(uuid => {
                                    const domain = assignedDomains.find(d => d.uuid === uuid);
                                    if (domain) {
                                        availableDomains.push(domain);
                                        assignedDomains = assignedDomains.filter(d => d.uuid !== uuid);
                                    }
                                });

                                selectedAssignedDomains = [];
                                renderAvailableDomains($('#searchAvailableDomains').val());
                                renderAssignedDomains($('#searchAssignedDomains').val());
                                updateDomainCounts();
                                updateDomainButtons();
                                updateDomainHiddenInputs();
                            }

                            function assignAllDomains() {
                                assignedDomains = [...assignedDomains, ...availableDomains];
                                availableDomains = [];
                                selectedAvailableDomains = [];

                                renderAvailableDomains($('#searchAvailableDomains').val());
                                renderAssignedDomains($('#searchAssignedDomains').val());
                                updateDomainCounts();
                                updateDomainButtons();
                                updateDomainHiddenInputs();
                            }

                            function removeAllDomains() {
                                availableDomains = [...availableDomains, ...assignedDomains];
                                assignedDomains = [];
                                selectedAssignedDomains = [];

                                renderAvailableDomains($('#searchAvailableDomains').val());
                                renderAssignedDomains($('#searchAssignedDomains').val());
                                updateDomainCounts();
                                updateDomainButtons();
                                updateDomainHiddenInputs();
                            }

                            function updateDomainCounts() {
                                $('#availableDomainsCount').text(availableDomains.length);
                                $('#assignedDomainsCount').text(assignedDomains.length);
                            }

                            function updateDomainButtons() {
                                $('#assignDomainsBtn').prop('disabled', selectedAvailableDomains.length === 0);
                                $('#removeDomainsBtn').prop('disabled', selectedAssignedDomains.length === 0);
                            }

                            function updateDomainHiddenInputs() {
                                const container = $('#domainsHiddenInputs');
                                container.empty();

                                assignedDomains.forEach(domain => {
                                    container.append(`<input type="hidden" name="domains[]" value="${domain.uuid}">`);
                                });
                            }

                            function setupDomainSearchFilters() {
                                $('#searchAvailableDomains').on('input', function() {
                                    renderAvailableDomains($(this).val());
                                });

                                $('#searchAssignedDomains').on('input', function() {
                                    renderAssignedDomains($(this).val());
                                });
                            }
                            </script>
                        </div>

                        <!-- Tags Tab -->
                        <div class="tab-pane fade" id="nav-tags" role="tabpanel" aria-labelledby="nav-tags-tab">
                            <div class="form-group col-md-12" style="margin-top: 20px;">
                                <label for="service_tags">
                                    <i class="fa fa-tags"></i> Tags
                                    <a href="/tags/manage" target="_blank" style="margin-left: 10px;">
                                        <i class="fa fa-cog"></i> Manage Tags
                                    </a>
                                </label>
                                <select id="service_tags" name="service_tags[]" class="form-control select2-tags" multiple="multiple">
                                    <!-- Populated by JavaScript -->
                                </select>
                                <small class="form-text text-muted">
                                    Select one or more tags to categorize this service. Tags help organize and filter services.
                                </small>
                            </div>

                            <style>
                                .select2-tags .select2-selection__choice {
                                    padding: 4px 8px;
                                    border-radius: 12px;
                                    font-weight: 600;
                                    border: none;
                                }
                            </style>

                            <script>
                                // Load tags for service
                                function loadServiceTags() {
                                    const serviceId = '<?= @$service->id ?>';

                                    $.ajax({
                                        url: '/tags/tagsList',
                                        method: 'GET',
                                        success: function(response) {
                                            const allTags = response.data;
                                            const $select = $('#service_tags');

                                            // Clear and populate select
                                            $select.empty();

                                            allTags.forEach(tag => {
                                                const option = new Option(tag.name, tag.id, false, false);
                                                $select.append(option);
                                            });

                                            // Initialize Select2
                                            $select.select2({
                                                placeholder: 'Select tags...',
                                                allowClear: true,
                                                width: '100%',
                                                templateResult: formatTagOption,
                                                templateSelection: formatTagSelection
                                            });

                                            // Load current tags if editing
                                            if (serviceId) {
                                                loadCurrentServiceTags(serviceId, allTags);
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Error loading tags:', error);
                                        }
                                    });
                                }

                                function formatTagOption(tag) {
                                    if (!tag.id) return tag.text;

                                    const tagData = $('#service_tags option[value="' + tag.id + '"]').data();
                                    const color = tagData?.color || '#667eea';

                                    return $('<span><span class="tag-color-dot" style="display:inline-block;width:10px;height:10px;border-radius:50%;background:' + color + ';margin-right:8px;"></span>' + tag.text + '</span>');
                                }

                                function formatTagSelection(tag) {
                                    if (!tag.id) return tag.text;

                                    const $option = $('#service_tags option[value="' + tag.id + '"]');
                                    const color = $option.data('color') || '#667eea';

                                    const $span = $('<span style="background:' + color + ';color:white;padding:4px 8px;border-radius:12px;font-weight:600;">' + tag.text + '</span>');
                                    return $span;
                                }

                                function loadCurrentServiceTags(serviceId, allTags) {
                                    $.ajax({
                                        url: '/tags/getEntityTags',
                                        method: 'GET',
                                        data: {
                                            entity_type: 'service',
                                            entity_id: serviceId
                                        },
                                        success: function(response) {
                                            if (response.tags && response.tags.length > 0) {
                                                // Store tag data in options
                                                allTags.forEach(tag => {
                                                    $('#service_tags option[value="' + tag.id + '"]').data('color', tag.color);
                                                });

                                                // Set selected tags
                                                const selectedTagIds = response.tags.map(t => t.id.toString());
                                                $('#service_tags').val(selectedTagIds).trigger('change');
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Error loading current tags:', error);
                                        }
                                    });
                                }

                                // Save tags when form is submitted
                                $('#addcustomer').on('submit', function(e) {
                                    const serviceId = '<?= @$service->id ?>';

                                    if (serviceId) {
                                        e.preventDefault();

                                        const selectedTags = $('#service_tags').val() || [];

                                        // Save tags first
                                        $.ajax({
                                            url: '/tags/saveEntityTags',
                                            method: 'POST',
                                            data: {
                                                entity_type: 'service',
                                                entity_id: serviceId,
                                                tag_ids: selectedTags
                                            },
                                            success: function(response) {
                                                // After tags are saved, submit the form normally
                                                $('#addcustomer').off('submit').submit();
                                            },
                                            error: function(xhr, status, error) {
                                                console.error('Error saving tags:', error);
                                                alert('Error saving tags. Please try again.');
                                            }
                                        });
                                    }
                                });

                                // Initialize tags when document is ready
                                $(document).ready(function() {
                                    loadServiceTags();
                                });
                            </script>
                        </div>

                    </div>
                </div>
            </div>

            <div class="form-row justify-content-end">
                <div class="form-group mr-3">
                    <button type="submit" class="btn btn-primary">Submit</button>
                </div>
                <div class="form-group mr-3">
                    <button type="button" id="DeployService" class="btn btn-primary page_title_right">
                        Deploy
                    </button>
                </div>
                <div class="form-group mr-3">
                    <button type="button" id="DeleteService" class="btn btn-primary page_title_right">
                        Delete
                    </button>
                </div>
            </div>
        </form>
        <?php /*<div class="btndata">
 <button type="button" id="RunCmd" class="btn btn-primary page_title_right">Run CMD</button>
</div> */ ?>
    </div>
</div>

<div 
    class="modal fade" 
    id="helmConfirmationModal" 
    tabindex="-1" role="dialog" 
    aria-labelledby="helmConfirmationModalTitle" 
    aria-hidden="true"
    data-backdrop="false"
>
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="helmModalTitle">Deploy to?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Please select the environment where you want to deploy</p>
        <form id="deploy-modal-form">
            <?php
            if (!empty($service->env_tags)) {
                $arr = explode(',', $service->env_tags);
                foreach ($arr as $key => $envTag) : ?>
                    <div class="form-check">
                        <input type="checkbox" name="<?= $envTag ?>" class="form-check-input select-env-tag" id="select-env_<?= $key ?>">
                        <label for="select-env_<?= $key ?>" class="form-check-label"><?= $envTag ?></label>
                    </div>
                <?php endforeach; ?>
            <?php } ?>
        </form>
        <span class="deply-message" id="deplyMessage"></span>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
        <button type="button" class="btn btn-primary" id="helm-deploy">Yes</button>
      </div>
    </div>
  </div>
</div>

<?php require_once(APPPATH . 'Views/common/footer.php'); ?>


<script>
    // Add the following code if you want the name of the file appear on select
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/additional-methods.min.js"></script>
<script>
    if ($("#addservice").length > 0) {
        $("#addservice").validate({
            rules: {
                name: {
                    required: true,
                },
                notes: {
                    required: true,
                },
                code: {
                    required: true,
                },
                uuid: {
                    required: true,
                },
                /* nginx_config: {
                   required: true,
                 }, 
                 varnish_config: {
                   required: true,
                 },   */
            },
            messages: {
                name: {
                    required: "Please enter name",
                },
                notes: {
                    required: "Please enter notes",
                },
                code: {
                    required: "Please enter code",
                },
                uuid: {
                    required: "Please select user",
                },

            },
        })
    }
</script>

<script type="text/javascript">
    var id = "<?= @$service->uuid ?>";

    $(document).ready(function() {
        $('#service_type').change(function() {
            if ($(this).val() === 'workflows') {
                $('#service-template-wrapper').removeClass('hidden');
                $('#marketing-wrapper').addClass('hidden');
            } else if ($(this).val() === 'marketing') {
                $('#service-template-wrapper').addClass('hidden');
                $('#marketing-wrapper').removeClass('hidden');
            }
        });
    });

    $(document).on('drop', '#drop_file_doc_zone', function(e) {
        // $("#ajax_load").show();
        if (e.originalEvent.dataTransfer) {
            if (e.originalEvent.dataTransfer.files.length) {
                e.preventDefault();
                e.stopPropagation();
                var i = 0;
                while (i < e.originalEvent.dataTransfer.files.length) {
                    newUploadDocFiles(e.originalEvent.dataTransfer.files[i], id);
                    i++;
                }
            }
        }
    });

    $(document).on("change", ".fileUpload", function() {

        for (var count = 0; count < $(this)[0].files.length; count++) {

            newUploadDocFiles($(this)[0].files[count], id);
        }

    });



    function newUploadDocFiles(fileobj, id) {

        $("#ajax_load").hide();

        var form = new FormData();

        form.append("file", fileobj);
        form.append("mainTable", class_name);
        form.append("id", id);

        $.ajax({
            url: '/services/uploadMediaFiles',
            type: 'post',
            dataType: 'json',
            maxNumberOfFiles: 1,
            autoUpload: false,
            success: function(result) {

                $("#ajax_load").hide();
                if (result.status == '1') {
                    $(".all-media-image-files").html(result.file_path);
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $("#ajax_load").hide();
                console.log(textStatus, errorThrown);
            },
            data: form,
            cache: false,
            contentType: false,
            processData: false


        });



    }

    $(document).on('drop', '#drop_file_doc_zone2', function(e) {
        // $("#ajax_load").show();
        if (e.originalEvent.dataTransfer) {
            if (e.originalEvent.dataTransfer.files.length) {
                e.preventDefault();
                e.stopPropagation();
                var i = 0;
                while (i < e.originalEvent.dataTransfer.files.length) {
                    newUploadDocFiles2(e.originalEvent.dataTransfer.files[i], id);
                    i++;
                }
            }
        }
    });

    $(document).on("change", ".fileUpload2", function() {

        for (var count = 0; count < $(this)[0].files.length; count++) {

            newUploadDocFiles2($(this)[0].files[count], id);
        }

    });



    function newUploadDocFiles2(fileobj, id) {

        $("#ajax_load").hide();

        var form = new FormData();

        form.append("file", fileobj);
        form.append("mainTable", class_name);
        form.append("id", id);

        $.ajax({
            url: '/services/uploadMediaFiles2',
            type: 'post',
            dataType: 'json',
            maxNumberOfFiles: 1,
            autoUpload: false,
            success: function(result) {

                $("#ajax_load").hide();
                if (result.status == '1') {
                    $(".all-media-image-files2").html(result.file_path);
                } else {
                    toastr.error(result.msg);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                $("#ajax_load").hide();
                console.log(textStatus, errorThrown);
            },
            data: form,
            cache: false,
            contentType: false,
            processData: false


        });



    }

    $(document).ready(function() {
        $("#helm-deploy").click(function() {
            selectedTags = [];
            var x = $(".select-env-tag");
            const serviceType = $("#service_type").val();
            $.each(x, function(i, field) {
                selectedTags.push({[field.name]: $(field).is(':checked')});
            });
            console.log({selectedTags});

            // Disable button and show loading state
            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Deploying...');
            $("#deplyMessage").html('<p class="text-info"><i class="fa fa-spinner fa-spin"></i> Deployment in progress...</p>');

            var Status = $(this).val();
            $.ajax({
                url: "/services/deploy_service/<?= @$service->uuid ?>",
                type: "post",
                data: {
                    'data': { Status,  selectedTags, serviceType}
                },
                success: function(response) {
                    const res = typeof(response) == "string" ? JSON.parse(response) : response;

                    // Handle different response formats
                    let messageHtml = '';

                    if (res.status == 200) {
                        messageHtml = `<p class="text-success"><i class="fa fa-check-circle"></i> ${res.message}</p>`;

                        // Show deployment details if available
                        if (res.deployments && res.deployments.length > 0) {
                            messageHtml += '<div style="margin-top:10px;"><strong>Deployment Details:</strong><ul style="margin-top:5px;">';
                            res.deployments.forEach(function(deployment) {
                                const statusIcon = deployment.success ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>';
                                const statusText = deployment.success ? 'Success' : 'Failed';
                                messageHtml += `<li>${statusIcon} <strong>${deployment.environment}</strong>: ${statusText}`;
                                if (deployment.deployment_uuid) {
                                    messageHtml += ` <small>(ID: ${deployment.deployment_uuid.substring(0, 8)}...)</small>`;
                                }
                                if (deployment.error) {
                                    messageHtml += `<br><small class="text-danger">Error: ${deployment.error}</small>`;
                                }
                                messageHtml += '</li>';
                            });
                            messageHtml += '</ul></div>';

                            // Show summary
                            if (res.total > 1) {
                                messageHtml += `<p style="margin-top:10px;"><strong>Summary:</strong> ${res.successful}/${res.total} deployments successful</p>`;
                            }
                        }
                    } else if (res.status == 207) {
                        // Partial success
                        messageHtml = `<p class="text-warning"><i class="fa fa-exclamation-triangle"></i> ${res.message}</p>`;

                        if (res.deployments && res.deployments.length > 0) {
                            messageHtml += '<div style="margin-top:10px;"><ul>';
                            res.deployments.forEach(function(deployment) {
                                const statusClass = deployment.success ? 'text-success' : 'text-danger';
                                const statusIcon = deployment.success ? '<i class="fa fa-check"></i>' : '<i class="fa fa-times"></i>';
                                messageHtml += `<li class="${statusClass}">${statusIcon} <strong>${deployment.environment}</strong>: ${deployment.success ? 'Success' : 'Failed'}`;
                                if (deployment.error) {
                                    messageHtml += `<br><small>Error: ${deployment.error}</small>`;
                                }
                                messageHtml += '</li>';
                            });
                            messageHtml += '</ul></div>';
                        }
                    } else {
                        // Error
                        messageHtml = `<p class="text-danger"><i class="fa fa-times-circle"></i> ${res.message || 'Deployment failed'}</p>`;
                        if (res.error) {
                            messageHtml += `<small class="text-muted">${res.error}</small>`;
                        }
                    }

                    $("#deplyMessage").html(messageHtml);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error({textStatus, errorThrown});
                    const errorMsg = jqXHR.responseJSON?.message || 'Deployment request failed. Please check logs.';
                    $("#deplyMessage").html(`<p class="text-danger"><i class="fa fa-times-circle"></i> ${errorMsg}</p>`);
                },
                complete: function() {
                    // Re-enable button
                    $btn.prop('disabled', false).html('Yes');
                }
            });
        });
    })

    $('#DeployService').on('click', function() {
        const serviceType = $("#service_type").val();
        if (serviceType === "workflows") {
            $('#helmConfirmationModal').modal('toggle');
        } else if (serviceType === "marketing") {
            $("#helm-deploy").click();
        }
        return false;
        var Status = $(this).val();
        $.ajax({
            url: "/services/deploy_service/<?= @$service->uuid ?>",
            type: "post",
            data: {
                'data': Status
            },
            success: function(response) {
                alert(response);


                // You will get response from your PHP page (what you echo or print)
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    });

    $('#DeleteService').on('click', function() {
        var Status = $(this).val();
        $.ajax({
            url: "/services/delete_service/<?= @$service->uuid ?>",
            type: "post",
            data: {
                'data': Status
            },
            success: function(response) {
                alert(response);


                // You will get response from your PHP page (what you echo or print)
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log(textStatus, errorThrown);
            }
        });
    });


    $(document).ready(function() {

        var max_fields_limit = 10; //set limit for maximum input fields
        var x = $('#total_secret_services').val(); //initialize counter for text box
        $('.add').click(function(e) { //click event on add more fields button having class add_more_button
            // e.preventDefault();
            if (x < max_fields_limit) { //check conditions
                x++; //counter increment

                $('.addresscontainer').append(`
                    <div class="form-row col-md-12" id="office_address_${x}">
                        <div class="form-group col-md-3">
                            <label for="inputSecretKey">Secret Key</label>
                            <input autocomplete="off" type="text" class="form-control" id="key_name_${x}" name="key_name[]" placeholder="" value="">
                        </div>
                        <div class="form-group col-md-5">
                            <label for="inputSecretValue">Secret Value</label>
                            <input autocomplete="off" type="text" class="form-control" id="key_value_${x}" name="key_value[]" placeholder="" value="">
                        </div>
                        <div class="form-group col-md-2 d-flex flex-column">
                            <label for="my-select2_${x}">Environment</label>
                            <select id="my-select2_${x}" data-select2-tags="true" name="secret_tags[]" class="form-control select2">
                                <option value="" >--Select--</option>
                            </select>
                        </div>
                        <div class="form-group col-md-1 change">
                            <button class="btn btn-info bootstrap-touchspin-up deleteaddress" data-type="secret_services" id="deleteRow" type="button" style="max-height: 35px;margin-top: 28px;margin-left: 10px;">-</button>
                        </div>
                        <div class="form-group col-md-1 change">
                            <button 
                                class="btn btn-info bootstrap-touchspin-up clone-row" 
                                data-type="secret_services" 
                                data-uuid="" 
                                data-id="office_address_${x}"
                                data-key="${x}"
                                id="cloneRow" 
                                type="button" 
                                style="max-height: 35px;margin-top: 28px;margin-left: 10px;"
                            >
                                <i class="fas fa-clone"></i>
                            </button>
                        </div>
                    </div>`
                );

            }
            $('.select2').select2({
                placeholder: "Select an Option",
                allowClear: true,
                tags: true,
            });
            
            $(".select2-container--default .select2-selection--single .select2-selection__clear").css("padding-right", "20px");
            $(".select2-container--default .select2-selection--single .select2-selection__clear").css("padding-top", "7px");

            $('.deleteaddress').on("click", function(e) { //user click on remove text links
                e.preventDefault();
                $(this).parent().parent().remove();
                x--;
            })
        });

        $(".select2-container--default .select2-selection--single .select2-selection__clear").css("padding-right", "20px");
        $(".select2-container--default .select2-selection--single .select2-selection__clear").css("padding-top", "7px");
    });

    $('.deleteaddress').on("click", function(e) { //user click on remove text links

        var current = $(this);
        var serviceId = current.attr("data-id");
        var serviceType = current.attr("data-type");
        var pageId = $("#serviceId").val();
        $.ajax({
            url: baseUrl + "/services/deleteRow",
            data: {
                id: serviceId,
                type: serviceType,
                sId: pageId
            },
            method: 'post',
            success: function(res) {
                console.log(res)
                current.parent().parent().remove();

            }
        })

    })

    $(document).on("click", "#cloneRow", function () {
        let row = $(this).attr("data-id");
        var originalField = document.querySelector(`#${row}`);
        var clonedField = originalField.cloneNode(true);
        var parentNode = document.getElementById("addresscontainer");
        var nodeCount = parentNode.childElementCount;

        clonedField.id = `office_address_${nodeCount + 1}`
        clonedField.children[0].children[1].id = `key_name_${nodeCount + 1}`;
        clonedField.children[1].children[1].id = `key_value_${nodeCount + 1}`;
        clonedField.children[1].children[1].value = ``;
        clonedField.children[1].children[1].defaultValue = ``;
        clonedField.removeChild(clonedField.children[2]);
        var envField = `
            <label for="my-select2_${nodeCount + 1}">Environment</label>
            <select id="my-select2_${nodeCount + 1}" data-select2-tags="true" name="secret_tags[]" class="form-control select2">
                <option value="" >--Select--</option>
            </select>
        `;
        var envNode = document.createElement("div");
        envNode.classList.add("form-group", "col-md-2", "d-flex", "flex-column");
        envNode.innerHTML = envField;
        clonedField.children[2].defaultValue = "";
        clonedField.children[2].value = "";
        var indexToInsertAt = 2;
        var existingElement = clonedField.children[indexToInsertAt];
        clonedField.insertBefore(envNode, existingElement);
        console.log({clonedField});
        document.getElementById('addresscontainer').appendChild(clonedField);
        $('.select2').select2({
            placeholder: "Select an Option",
            allowClear: true,
            tags: true,
        });
    })

    // Add the following code if you want the name of the file appear on select
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });

    $("#file2").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings("#file2").addClass("selected").html(fileName);
    });

    $("#delete_image_logo").on("click", function(e) {
        e.preventDefault();
        $(".all-media-image-files").html("");
    })
    $("#delete_image_logo2").on("click", function(e) {
        e.preventDefault();
        $(".all-media-image-files").html("");
    })

    $(document).ready(function() {

        $('[data-toggle="tooltip"]').tooltip();

        var max_fields_limit = 20; //set limit for maximum input fields
        total_blocks = parseInt($('#total_blocks').val()); //initialize counter for text box

        $('.add_step').click(function(e) { //click event on add more fields button having class add_more_button

            $('.blocks_html').append('<div class="form-row col-md-12 each-block" style="margin-bottom:30px;" id="blocks_html_' + total_blocks + '"><div class="form-group col-md-6">' +
                '<label for="inputSecretKey">Code</label>' +
                '<input type="text" class="form-control blocks_code" id="blocks_code' + total_blocks + '" name="blocks_code[]" placeholder="" value=""><br>' +

                '<label for="inputSecretValue">Title</label>' +
                '<input type="text" class="form-control" id="blocks_title' + total_blocks + '" name="blocks_title[]" placeholder="" value=""><br>' +
                '<label for="inputEmail4">Sort</label>' +
                '<input autocomplete="off" type="number" class="form-control"  name="sort[]" placeholder="" value="">' +

                '<label for="inputEmail4">Type</label>' +
                '<select name="type[]" id="text_type" class="form-control text_type">' +
                '<option value="database">Database</option>' +
                '<option value="nginx">Nginx</option>' +
                '<option value="dns" >DNS</option>' +
                '<option value="varnish" >Varnish</option>' +
                '<option value="secrets" >Secrets	</option>' +
                '<option value="bash">Bash</option>' +
                '</select>' +

                '</div>' +
                '<div class="form-group col-md-5 textarea-block">' +
                '<label class="textarea_label" for="inputSecretValue">Text</label>' +
                '<textarea class="form-control textarea-height blocks_text" id="blocks_text' + total_blocks + '" name="blocks_text[]" placeholder="" value="" ></textarea> ' +
                '</div> <input type="hidden" value="0" id="blocks_id" name="blocks_id[]">' +
                '<div class="form-group col-md-1 change">' +
                '<button class="btn btn-info bootstrap-touchspin-up deleteaddress" data-type="service_step" id="deleteRow" type="button" style="max-height: 35px;margin-top: 28px;margin-left: 10px;">-</button>' +
                '</div></div>'
            );

            total_blocks++;

            CKEDITOR.replaceAll('myClassName');

            $('.deleteaddress').on("click", function(e) { //user click on remove text links

                $(this).parent().parent().remove();
                total_blocks--;

            })
        });
    });



    $(document).ready(function() {

        var max_fields_limit = 10; //set limit for maximum input fields
        var x = $('#total_domains').val(); //initialize counter for text box
        $('.add_domain').click(function(e) { //click event on add more fields button having class add_more_button
            // e.preventDefault();
            if (x < max_fields_limit) { //check conditions
                x++; //counter increment

                $('.domains_container').append('<div class="form-row col-md-12" id="domains_' + x + '"><div class="form-group col-md-6">' +
                    '<label for="inputSecretKey">Select Domain</label>' +
                    '<select id="domains" name="domains[]" class="form-control">                                      <option value="" selected="">--Select--</option> <?php foreach ($all_domains as $row) : ?>     <option value="<?= $row['uuid']; ?>"><?= $row['name']; ?></option> <?php endforeach; ?> </select></div>' +
                    '<div class="form-group col-md-1 change">' +
                    '<button class="btn btn-info bootstrap-touchspin-up deleteaddress" data-type="domains" id="deleteRow" type="button" style="max-height: 35px;margin-top: 28px;margin-left: 10px;">-</button>' +
                    '</div></div>'
                );


            }

            $('.deleteaddress').on("click", function(e) { //user click on remove text links
                e.preventDefault();
                $(this).parent().parent().remove();
                x--;
            })
        });
        var templates = <?php echo json_encode($templates); ?>;
        var secretKeys = <?php echo !empty($tempKeys) ? json_encode($tempKeys) : "[]" ?>;
        console.log({secretKeys});
        function getCodeByUuid(uuid) {
            for (var i = 0; i < templates.length; i++) {
                if (templates[i].uuid === uuid) {
                    return templates[i].code;
                }
            }
            return null;
        }

        function findSecretKey(secretTempId) {
            var secretKey = null;
            $.each(secretKeys, function(index, item) {
                if (item.secret_temp_id === secretTempId) {
                    secretKey = item.secret_key;
                    return false;
                }
            });
            return secretKey;
        }
        function findValSecretKey(secretTempId, valueTempId) {
            var secretKey = null;
            $.each(secretKeys, function(index, item) {
                if (item.secret_temp_id === secretTempId && item.values_temp_id === valueTempId) {
                    secretKey = item.values_key;
                    return false;
                }
            });
            return secretKey;
        }

        function addSecKeyInput (secKeys) {
            $.each(secKeys, function (idx, selectedSec) {
                var secTempName = getCodeByUuid(selectedSec);
                var secKey = findSecretKey(selectedSec);
                var keyHtml = `
                    <div class="col-md-12">
                        <label for="secKeysName${idx}">Take keys From: ${secTempName}</label>
                        <select id="secKeysName${idx}" data-select2-tags="true" name="secKeysName[${selectedSec}][]" class="form-control select2">
                        <option value="${secKey ? secKey : ''}" selected >${secKey ? secKey : ''}</option>
                        </select>
                    </div>
                `;
                $("#secretKeysName").append(keyHtml);
                selectRefresh();
            });
        }
        function addValuesKeyInput (secKeys, tempId) {
            $.each(secKeys, function (idx, selectedSec) {
                var secTempName = getCodeByUuid(selectedSec);
                var valKey = findValSecretKey(selectedSec, tempId)
                var keyHtml = `
                    <div class="col-md-12">
                        <label for="valKeysName${idx}">Add Keys For: ${secTempName}</label>
                        <select id="valKeysName${idx}" data-select2-tags="true" name="valKeysName[${tempId}/${selectedSec}][]" class="form-control select2">
                        <option value="${valKey ? valKey : ''}" selected >${valKey ? valKey : ''}</option>
                        </select>
                    </div>
                `;
                $("#valuesKeysName").append(keyHtml);
                selectRefresh();
            });
        }

        var previousSelectedValues = <?php echo json_encode($secret_values_templates['secret_template_id']); ?>;
        var prevValTemps = "<?php echo $secret_values_templates['values_template_id'] ?? "false"; ?>";
        addSecKeyInput(previousSelectedValues);
        addValuesKeyInput(previousSelectedValues, prevValTemps);
        $('#secret_template').on('change', function() {
            var selectedSecretValues = $(this).val();
            var selectedValues = $("#values_template").val();
            $("#secretKeysName").html("");
            addSecKeyInput(selectedSecretValues);
            if (selectedValues) {
                $("#valuesKeysName").html("");
                addValuesKeyInput(selectedSecretValues, selectedValues);
            }
        });
        $('#values_template').on('change', function() {
            var selectedValues = $(this).val();
            var selectedSecretValues = $("#secret_template").val();
            $("#valuesKeysName").html("");
            addValuesKeyInput(selectedSecretValues, selectedValues);
        });
    });
</script>