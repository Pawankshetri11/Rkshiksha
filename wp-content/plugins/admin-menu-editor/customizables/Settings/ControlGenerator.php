<?php

namespace YahnisElsts\AdminMenuEditor\Customizable\Settings;

use YahnisElsts\AdminMenuEditor\Customizable\Builders\ElementBuilder;
use YahnisElsts\AdminMenuEditor\Customizable\Builders\ElementBuilderFactory;
use YahnisElsts\AdminMenuEditor\Customizable\Controls\UiElement;

/**
 * Implement this interface to provide custom controls for a setting or a group of settings.
 */
interface ControlGenerator {
	/**
	 * Create controls for editing the setting(s) defined by this generator.
	 *
	 * When this method returns multiple controls, it's up to the caller to decide how to arrange them
	 * in the user interface. They could be put in a new section, or displayed inline with other controls.
	 * If the interface implementer needs to group the controls together, it can explicitly create
	 * a section or other container and return it as a single control.
	 *
	 * @param ElementBuilderFactory $b
	 * @return array<ElementBuilder|UiElement>
	 */
	public function createControls(ElementBuilderFactory $b): array;
}