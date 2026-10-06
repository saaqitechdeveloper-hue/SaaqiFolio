<?php
// Expects $img (array) and $categories (array) to be set by the including page.
?>
<div class="card" data-img-id="<?php echo (int) $img['id']; ?>" data-category="<?php echo e($img['category']); ?>" draggable="true">
  <div class="thumb-wrap">
    <div class="card-drag-handle" title="Drag to reorder in this category" aria-label="Drag to reorder">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13">
        <circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/>
        <circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/>
        <circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/>
      </svg>
    </div>

    <label class="img-select-box" style="display:none;" title="Select image">
      <input type="checkbox" class="img-select-cb" data-id="<?php echo (int) $img['id']; ?>">
      <span class="custom-check-box">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
      </span>
    </label>

    <img src="../assets/uploads/portfolio/<?php echo e($img['filename']); ?>" class="thumb" loading="lazy" alt="Portfolio asset">

    <div class="card-overlay-actions">
      <form method="POST" class="delete-img-form" onsubmit="return confirm('Are you sure you want to delete this piece?');">
        <input type="hidden" name="image_id" value="<?php echo (int) $img['id']; ?>">
        <button type="submit" name="delete_image" class="delete-btn-glass" title="Delete image" aria-label="Delete image">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
            <line x1="10" y1="11" x2="10" y2="17"/>
            <line x1="14" y1="11" x2="14" y2="17"/>
          </svg>
        </button>
      </form>
    </div>
  </div>

  <div class="card-foot">
    <div class="cat-dropdown-wrap">
      <form method="POST" class="cat-inline-form">
        <input type="hidden" name="image_id" value="<?php echo (int) $img['id']; ?>">
        <input type="hidden" name="set_category" value="1">
        <input type="hidden" name="category" value="<?php echo e($img['category']); ?>">
      </form>
      <button type="button" class="badge-pill badge-<?php echo slugify($img['category']); ?> cat-trigger" tabindex="0">
        <span class="badge-dot"></span>
        <span class="cat-text"><?php echo e($img['category']); ?></span>
        <svg class="cat-caret-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
      <ul class="cat-menu" hidden>
        <?php foreach ($categories as $c): ?>
          <li class="cat-menu-item <?php echo $img['category'] === $c ? 'active' : ''; ?>" data-value="<?php echo e($c); ?>">
            <span class="cat-item-dot cat-dot-<?php echo slugify($c); ?>"></span>
            <span><?php echo e($c); ?></span>
            <?php if ($img['category'] === $c): ?>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="12" height="12" style="margin-left:auto;"><polyline points="20 6 9 17 4 12"/></svg>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
