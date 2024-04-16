<?php
/**
 * @var \App\View\AppView $this
 */
?>
<?=
$this->Form->create(null, [
    'url' => ['controller' => 'Links', 'action' => 'shorten', 'prefix' => false],
    'id' => 'shorten',
]);
?>

<?php
$this->Form->templates([
    'inputContainer' => '{{content}}',
    'error' => '{{content}}',
    'inputContainerError' => '{{content}}',
]);
?>

<div class="form-group">
    <div class="input-group">
        <?=
        $this->Form->control('url', [
            'label' => false,
            'type' => 'text',
            'placeholder' => __('Your URL Here'),
            'required' => 'required',
            'class' => 'form-control input-lg',
        ]);
        ?>

        <?php
        $ad_type = get_option('anonymous_default_advert', 1);

        if (null !== $this->request->session()->read('Auth.User.id')) {
            $ad_type = get_option('member_default_advert', 1);
        }

        if (!array_key_exists($ad_type, get_allowed_ads())) {
            $ad_type = array_key_first(get_allowed_ads());
        }
        ?>

        <?= $this->Form->hidden('ad_type', ['value' => $ad_type]); ?>

        <div class="input-group-addon">
            <?= $this->Form->button('<i class="fa fa-paper-plane fasubmit"></i>', [
                'class' => 'btn-captcha',
                'id' => 'invisibleCaptchaShort',
            ]); ?>
        </div>
    </div>
</div>

<?php if (!$this->request->session()->read('Auth.User.id') &&
    (bool)get_option('enable_captcha_shortlink_anonymous', false) &&
    isset_captcha()
) : ?>
    <div class="form-group captcha" style="display: none">
        <div id="captchaShort" style="display: inline-block;"></div>
    </div>
    <?php
    $this->Form->unlockField('g-recaptcha-response');
    $this->Form->unlockField('h-captcha-response');
    $this->Form->unlockField('adcopy_challenge');
    $this->Form->unlockField('adcopy_response');
    ?>
<?php endif; ?>

<?= $this->Form->end(); ?>

<div class="shorten add-link-result"></div>
