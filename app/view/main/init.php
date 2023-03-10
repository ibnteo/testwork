<div class="container py-3">
    <div class="row">
		<div class="col-md-6">
			<h1><?php echo htmlspecialchars($name ?? ''); ?></h1>
			<form method="POST" action="save">
				<div class="mb-3">
					<label for="name">Имя:</label>
					<input class="form-control" name="name" id="name" maxlength="128" required>
				</div>
				<div class="mb-3">
					<label for="message">Отзыв:</label>
					<textarea class="form-control" name="message" id="message" rows="10" required></textarea>
				</div>
				<button class="btn btn-primary" type="submit">Отправить</button>
			</form>
		</div>
		<div id="reviews" class="col-md-6">
			<?php
				include tpl('main.review');
			?>
		</div>
	</div>
</div>
