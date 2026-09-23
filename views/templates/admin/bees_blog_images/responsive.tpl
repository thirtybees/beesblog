{*
 * Responsive image migration and regeneration dashboard.
 *}
<form id="beesblog-responsive-dashboard" class="form-horizontal" action="#" method="post" onsubmit="return false;">
<div class="panel">
    <h3><i class="icon-picture"></i> {l s='Regenerate responsive images' mod='beesblog'}</h3>

    <div class="alert alert-info">
        {l s='Regenerates responsive files for all existing blog source images.' mod='beesblog'}<br>
        {l s='Generation runs one image per request and can be paused or safely resumed.' mod='beesblog'}<br>
        {l s='Existing responsive images stay live until their replacements have been generated and validated.' mod='beesblog'}
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">
            {l s='Regenerate images' mod='beesblog'}
        </label>
        <table class="col-lg-9">
            <tbody>
                {foreach from=$beesblogResponsiveStatuses key=entityType item=status}
                    {assign var=total value=$status.total|intval}
                    {assign var=completed value=$status.completed|intval}
                    {assign var=pending value=$status.pending|intval}
                    {assign var=failed value=$status.failed|intval}
                    <tr data-responsive-row="{$entityType|escape:'htmlall':'UTF-8'}">
                        <td style="padding-bottom: 10px; white-space: nowrap; vertical-align: middle;">
                            <button type="button"
                                    class="btn btn-info beesblog-responsive-action"
                                    data-entity-type="{$entityType|escape:'htmlall':'UTF-8'}"
                                    data-mode="missing"
                                    {if !$total || (!$pending && !$failed)}disabled="disabled"{/if}>
                                <i class="icon icon-play"></i>
                                <span>{l s='Regenerate %s' sprintf=[$status.display_name] mod='beesblog'}</span>
                            </button>
                        </td>
                        <td width="99%" style="padding-left: 20px; padding-bottom: 10px; vertical-align: middle;">
                            <div class="progress{if !$total} disabled{/if}" style="margin: 0; position: relative;">
                                <div class="progress-bar{if $failed} progress-bar-warning{/if}"
                                     role="progressbar"
                                     data-responsive-progress="{$entityType|escape:'htmlall':'UTF-8'}"
                                     style="width: {if $total}{($completed * 100 / $total)|intval}{else}0{/if}%; min-width: 0; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;">
                                    <span style="position: absolute; left: 5px; right: 5px; text-align: center; white-space: nowrap;">
                                        <span data-responsive-completed="{$entityType|escape:'htmlall':'UTF-8'}">{$completed}</span>
                                        /
                                        <span data-responsive-progress-total="{$entityType|escape:'htmlall':'UTF-8'}">{$total}</span>
                                        <span data-responsive-pending-wrap="{$entityType|escape:'htmlall':'UTF-8'}"{if !$pending} style="display:none"{/if}>
                                            &nbsp;(<span data-responsive-pending="{$entityType|escape:'htmlall':'UTF-8'}">{$pending}</span> {l s='missing' mod='beesblog'})
                                        </span>
                                        <span data-responsive-failed-wrap="{$entityType|escape:'htmlall':'UTF-8'}"{if !$failed} style="display:none"{/if}>
                                            &nbsp;(<span data-responsive-failed="{$entityType|escape:'htmlall':'UTF-8'}">{$failed}</span> {l s='failed' mod='beesblog'})
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    </div>

    <div class="form-group">
        <div class="col-lg-9 col-lg-offset-3">
            <p class="help-block">
                {l s='Progress counts source images, not blog records without an image. Resetting generation status does not delete existing responsive files.' mod='beesblog'}
            </p>
        </div>
    </div>

    <div class="panel-footer">
        <button type="button" class="btn btn-default" id="beesblog-responsive-reset">
            <i class="process-icon-refresh"></i> {l s='Reset generation status' mod='beesblog'}
        </button>
        <button type="button" class="btn btn-default pull-right" id="beesblog-responsive-regenerate-all">
            <i class="process-icon-cogs"></i> {l s='Regenerate all responsive images' mod='beesblog'}
        </button>
    </div>
</div>
</form>

<script type="text/javascript">
    (function () {
        'use strict';

        var ajaxUrl = '{$beesblogResponsiveAjaxUrl|escape:'javascript':'UTF-8'}';
        var running = { posts: false, categories: false };
        var activeButton = { posts: null, categories: null };
        var labels = {
            pause: '{l s='Pause' mod='beesblog' js=1}',
            completed: '{l s='Responsive image generation completed.' mod='beesblog' js=1}',
            completedWithErrors: '{l s='Responsive image generation finished, but one or more images failed. Use the row button to retry them.' mod='beesblog' js=1}',
            confirmReset: '{l s='Reset generation status for all responsive blog images? Existing generated files will not be deleted.' mod='beesblog' js=1}',
            resetCompleted: '{l s='Responsive image generation status was reset.' mod='beesblog' js=1}',
            pauseBeforeReset: '{l s='Pause the running image generation before resetting its status.' mod='beesblog' js=1}',
            nothingPending: '{l s='No responsive images are pending. Reset generation status to regenerate them again.' mod='beesblog' js=1}',
            requestFailed: '{l s='The responsive image request failed.' mod='beesblog' js=1}'
        };

        function request(action, data, success, complete) {
            return $.ajax({
                url: ajaxUrl + '&ajax=1&action=' + action,
                method: 'POST',
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify(data || {}),
                success: success,
                error: function (xhr) {
                    var message = labels.requestFailed;
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message += ' ' + xhr.responseJSON.message;
                    }
                    showErrorMessage(message);
                    if (data && data.entity_type && running[data.entity_type]) {
                        resetButton(data.entity_type);
                    }
                },
                complete: complete
            });
        }

        function updateStatus(statuses) {
            $.each(statuses || {}, function (entityType, status) {
                var total = parseInt(status.total, 10) || 0;
                var completed = parseInt(status.completed, 10) || 0;
                var pending = parseInt(status.pending, 10) || 0;
                var failed = parseInt(status.failed, 10) || 0;
                var percentage = total ? Math.floor((completed / total) * 100) : 0;
                var $progress = $('[data-responsive-progress="' + entityType + '"]');

                $('[data-responsive-progress-total="' + entityType + '"]').text(total);
                $('[data-responsive-completed="' + entityType + '"]').text(completed);
                $('[data-responsive-pending="' + entityType + '"]').text(pending);
                $('[data-responsive-failed="' + entityType + '"]').text(failed);
                $('[data-responsive-pending-wrap="' + entityType + '"]').toggle(pending > 0);
                $('[data-responsive-failed-wrap="' + entityType + '"]').toggle(failed > 0);
                $progress.css('width', percentage + '%').toggleClass('progress-bar-warning', failed > 0);
                $progress.closest('.progress').toggleClass('disabled', total === 0);

                $('[data-entity-type="' + entityType + '"][data-mode="missing"]')
                    .prop('disabled', total === 0 || (pending === 0 && failed === 0));
            });
        }

        function reportErrors(response) {
            if (!response || !response.hasError) {
                return;
            }
            $.each(response.errors || [], function (index, error) {
                if (error) {
                    showErrorMessage(error);
                }
            });
        }

        function resetButton(entityType) {
            var $button = activeButton[entityType];
            running[entityType] = false;
            if ($button && $button.length) {
                $button.find('i').removeClass('icon-pause icon-spin')
                    .addClass('icon-play');
                $button.find('span').text($button.data('original-label'));
            }
            activeButton[entityType] = null;
        }

        function processNext(entityType) {
            if (!running[entityType]) {
                return;
            }
            request('GenerateResponsiveImage', { entity_type: entityType }, function (response) {
                updateStatus(response.statuses);
                reportErrors(response);

                var status = response.statuses && response.statuses[entityType];
                if (!running[entityType]) {
                    return;
                }
                if (!status || parseInt(status.pending, 10) === 0) {
                    resetButton(entityType);
                    if (status && parseInt(status.failed, 10) > 0) {
                        showErrorMessage(labels.completedWithErrors);
                    } else {
                        showSuccessMessage(labels.completed);
                    }
                    return;
                }
                processNext(entityType);
            }, function () {
                if (running[entityType] && !activeButton[entityType]) {
                    resetButton(entityType);
                }
            });
        }

        function start(entityType, mode, $button) {
            if (running[entityType]) {
                resetButton(entityType);
                return;
            }
            running[entityType] = true;
            activeButton[entityType] = $button;
            if (!$button.data('original-label')) {
                $button.data('original-label', $button.find('span').text());
            }
            $button.find('i').removeClass('icon-play icon-refresh').addClass('icon-pause');
            $button.find('span').text(labels.pause);

            request('PrepareResponsiveImages', { entity_type: entityType, mode: mode }, function (response) {
                updateStatus(response.statuses);
                reportErrors(response);
                if (response.hasError) {
                    resetButton(entityType);
                    return;
                }
                processNext(entityType);
            }, function () {});
        }

        function startAll(mode) {
            var started = false;
            $('.beesblog-responsive-action[data-mode="' + mode + '"]').each(function () {
                if (!this.disabled) {
                    started = true;
                    start($(this).data('entity-type'), mode, $(this));
                }
            });
            if (!started) {
                showErrorMessage(labels.nothingPending);
            }
        }

        $(document).ready(function () {
            $('.beesblog-responsive-action').on('click', function () {
                start($(this).data('entity-type'), $(this).data('mode'), $(this));
            });
            $('#beesblog-responsive-regenerate-all').on('click', function () { startAll('missing'); });
            $('#beesblog-responsive-reset').on('click', function () {
                var $button = $(this);
                if (running.posts || running.categories) {
                    showErrorMessage(labels.pauseBeforeReset);
                    return;
                }
                if (!window.confirm(labels.confirmReset)) {
                    return;
                }
                $button.prop('disabled', true);
                request('ResetResponsiveImageStatus', {}, function (response) {
                    updateStatus(response.statuses);
                    reportErrors(response);
                    if (!response.hasError) {
                        showSuccessMessage(labels.resetCompleted);
                    }
                }, function () {
                    $button.prop('disabled', false);
                });
            });
        });
    }());
</script>
