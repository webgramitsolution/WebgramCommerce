<?php
class FakeModule extends Webgram\Core\Abstracts\Module {
	public function id(): string { return 'fake'; }
	public function name(): string { return 'Fake'; }
	public function dependencies(): array { return ['woocommerce']; }
	public function boot(): void {}
}
