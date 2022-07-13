              <ul class="pagination pagination-sm">
                <?php if($pager->hasPrev()): ?>
                <li><a href="<?= $pager->prevPath() ?>">&laquo;</a></li>
                <?php else: ?>
                <li class="disabled"><a href="#">&laquo;</a></li>
                <?php endif; ?>
                <li class="disabled"><a href="#"><?= $pager->from() ?> - <?= $pager->to() ?> / <?= $pager->count ?> </a></li>
                <?php if($pager->hasNext()): ?>
                <li><a href="<?= $pager->nextPath() ?>">&raquo;</a></li>
                <?php else: ?>
                <li class="disabled"><a href="#">&raquo;</a></li>
                <?php endif; ?>
              </ul>
