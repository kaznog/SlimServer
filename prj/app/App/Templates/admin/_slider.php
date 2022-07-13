
<div class="col-md-12">
  <div class="row">
    <div class="col-md-1">
      <?php if($pager->hasPrev()): ?>
        <button type="button" class="btn btn-default"><a href="<?= $pager->prevPath() ?>"><span class="glyphicon glyphicon-chevron-left"></span></a></button>
      <?php else: ?>
        <button class="btn btn-default disabled"><a href="#"><span class="glyphicon glyphicon-chevron-left"></span></a></button>
      <?php endif; ?>
    </div>
    <div class="col-md-10">
      <input type="text" class="span2" id="slider1" value="" style="width:940px">
    </div>
    <div class="col-md-1">
      <?php if($pager->hasNext()): ?>
        <button class="btn btn-default"><a href="<?= $pager->nextPath() ?>"><span class="glyphicon glyphicon-chevron-right"></span></a></button>
      <?php else: ?>
        <button class="btn btn-default disabled"><a href="#"><span class="glyphicon glyphicon-chevron-right"></span></a></button>
      <?php endif; ?>
    </div>
  </div><!-- /row -->
  <div class="row">
    <div class="col-md-3 col-md-offset-9 text-right">
      <?= $pager->from() ?> - <?= $pager->to() ?> / <?= $pager->count ?>
    </div>
  </div><!-- /row -->
</div>

<script type="text/javascript">
$('#slider1').slider({
    formater: function (v) {
        return v * 100 + 1;
    },
        max: <?= floor(($pager->count - 1) / $pager->limit) ?>,
        value: <?= $pager->page ?>,
})
.on('slideStop', function (ev) {
    var url = "<?= $pager->getPath() ?>" + ev.value;
    location.href = url;
  });
</script>


