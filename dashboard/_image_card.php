<?php
// Expects $img (array) and $categories (array) to be set by the including page.
?>
<div class="card">
  <div class="thumb-wrap">
    <div class="img-select-box" style="display:none;position:absolute;top:8px;left:8px;z-index:2;">
      <input type="checkbox" class="img-select-cb" data-id="<?php echo $img['id']; ?>">
    </div>
    <img src="../assets/uploads/portfolio/<?php echo e($img['filename']); ?>" class="thumb">
    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this image?');">
      <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
      <button type="submit" name="delete_image" class="delete-btn" aria-label="Delete image" style="opacity:1;">✕</button>
    </form>
  </div>
  <div class="card-foot">
    <div class="cat-dropdown-wrap" style="position:relative;display:inline-block;">
      <form method="POST" class="cat-inline-form">
        <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
        <input type="hidden" name="set_category" value="1">
        <select name="category" class="badge badge-<?php echo slugify($img['category']); ?>" style="border:none;cursor:pointer;" onchange="this.form.submit()">
          <?php foreach ($categories as $c): ?>
            <option value="<?php echo e($c); ?>" <?php echo $img['category'] === $c ? 'selected' : ''; ?>><?php echo e($c); ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>
  </div>
</div>
