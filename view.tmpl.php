<div class="container">
    <?php foreach ($responses as $response): ?>
    <div class="row">
        <?php foreach ($response as $value): ?>
        <div class="col-md">
            <?= $value ?>
        </div>
        <?php endforeach ?>
    </div>
    <br>
    <?php endforeach ?>
</div>
