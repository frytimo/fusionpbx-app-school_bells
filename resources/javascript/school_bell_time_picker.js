/**
 * School Bell Time Picker
 * Modern cron-style scheduling interface with toggle buttons
 * Uses HTML5 and vanilla JavaScript
 */

/**
 * Initializes the school bell schedule time picker
 * @access public
 * @return void
 */
function school_bell_schedule_time_init() {
	var container = document.getElementById('school_bell_schedule_time_picker');
	if (!container) {
		return;
	}

	// Clear container
	container.innerHTML = '';

	// Add CSS styles
	school_bell_inject_styles();

	// Create cron preview display at top
	school_bell_create_cron_preview(container);

	// Create the interface sections
	school_bell_create_minute_section(container);
	school_bell_create_hour_section(container);
	school_bell_create_day_of_month_section(container);
	school_bell_create_month_section(container);
	school_bell_create_day_of_week_section(container);

	// Initialize from hidden fields
	school_bell_populate_from_hidden();

	// Update preview
	school_bell_update_cron_preview();
}

/**
 * Injects CSS styles for the time picker
 * @access private
 * @return void
 */
function school_bell_inject_styles() {
	if (document.getElementById('school_bell_time_picker_styles')) {
		return;
	}

	var style = document.createElement('style');
	style.id = 'school_bell_time_picker_styles';
	style.textContent = `
		#school_bell_schedule_time_picker {
			max-width: 60%;
		}
		@media (max-width: 768px) {
			#school_bell_schedule_time_picker {
				max-width: 100%;
			}
		}
		.school_bell_section {
			margin-bottom: 20px;
			padding: 15px;
			border: 1px solid #ddd;
			border-radius: 4px;
			background: #f9f9f9;
		}
		.school_bell_section h4 {
			margin: 0 0 10px 0;
			font-size: 14px;
			font-weight: bold;
		}
		.school_bell_toggle_btn {
			padding: 6px 10px;
			margin: 2px;
			border: 2px solid #ccc;
			border-radius: 4px;
			background: #fff;
			cursor: pointer;
			transition: all 0.2s;
			font-size: 12px;
			min-width: 40px;
			text-align: center;
		}
		.school_bell_toggle_btn:hover {
			border-color: #999;
		}
		.school_bell_toggle_btn.active {
			background: #4CAF50;
			color: white;
			border-color: #4CAF50;
		}
		.school_bell_grid {
			display: grid;
			gap: 4px;
			margin-top: 10px;
		}
		.school_bell_min_grid {
			grid-template-columns: repeat(12, 1fr);
		}
		.school_bell_hour_grid {
			grid-template-columns: repeat(12, 1fr);
		}
		.school_bell_week_grid {
			grid-template-columns: repeat(7, 1fr);
		}
		.school_bell_month_grid {
			grid-template-columns: repeat(6, 1fr);
		}
		.school_bell_day_grid {
			grid-template-columns: repeat(7, 1fr);
		}
		.school_bell_step_input {
			margin-top: 10px;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.school_bell_step_input input {
			width: 70px;
			padding: 5px;
			border: 1px solid #ccc;
			border-radius: 3px;
		}
		.school_bell_cron_preview {
			padding: 15px;
			background: #e8f5e9;
			border: 2px solid #4CAF50;
			border-radius: 4px;
			margin-bottom: 20px;
		}
		.school_bell_cron_floating {
			display: inline-block;
			margin-left: 20px;
			padding: 5px 10px;
			background: #e8f5e9;
			border: 1px solid #4CAF50;
			border-radius: 3px;
			font-size: 12px;
		}
		.school_bell_cron_floating .school_bell_cron_value {
			font-size: 12px;
			padding: 3px 6px;
			display: inline-block;
		}
		.school_bell_cron_preview h4 {
			margin: 0 0 10px 0;
			font-size: 14px;
			color: #2E7D32;
			font-weight: bold;
		}
		.school_bell_cron_value {
			font-family: monospace;
			font-size: 16px;
			font-weight: bold;
			padding: 8px;
			background: white;
			border: 1px solid #4CAF50;
			border-radius: 3px;
			display: inline-block;
		}
	`;
	document.head.appendChild(style);
}

/**
 * Creates cron preview display
 * @access private
 * @param {HTMLElement} container The container element
 * @return void
 */
function school_bell_create_cron_preview(container) {
	var preview = document.createElement('div');
	preview.className = 'school_bell_cron_preview';
	preview.innerHTML = `
		<h4>Schedule Preview (Cron Format)</h4>
		<div class="school_bell_cron_value" id="school_bell_cron_display">* * * * *</div>
		<div style="margin-top: 8px; font-size: 12px; color: #666;">
			Format: Minute Hour DayOfMonth Month DayOfWeek
		</div>
	`;
	container.appendChild(preview);
}

/**
 * Creates minute section with toggle buttons
 * @access private
 * @param {HTMLElement} container The container element
 * @return void
 */
function school_bell_create_minute_section(container) {
	var section = document.createElement('div');
	section.className = 'school_bell_section';
	section.innerHTML = '<h4>Minute</h4>';

	// Minute buttons (0-59)
	var grid = document.createElement('div');
	grid.className = 'school_bell_grid school_bell_min_grid';
	for (var i = 0; i < 60; i++) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'school_bell_toggle_btn';
		btn.textContent = i < 10 ? '0' + i : i;
		btn.setAttribute('data-value', i);
		btn.onclick = function() {
			this.classList.toggle('active');
			school_bell_update_from_buttons('min');
		};
		grid.appendChild(btn);
	}
	section.appendChild(grid);

	// Step option
	var step_container = document.createElement('div');
	step_container.className = 'school_bell_step_input';
	step_container.innerHTML = `
		<label>
			<input type="checkbox" id="min_step_enable">
			Every
		</label>
		<input type="number" id="min_step_value" min="1" max="59" placeholder="15" disabled>
		<span>minute(s)</span>
	`;
	section.appendChild(step_container);

	container.appendChild(section);

	// Event listeners
	document.getElementById('min_step_enable').addEventListener('change', function() {
		var step_input = document.getElementById('min_step_value');
		step_input.disabled = !this.checked;
		if (this.checked) {
			school_bell_deactivate_buttons('min');
		}
		school_bell_update_from_buttons('min');
	});

	document.getElementById('min_step_value').addEventListener('change', function() {
		school_bell_update_from_buttons('min');
	});
}

/**
 * Creates hour section with toggle buttons
 * @access private
 * @param {HTMLElement} container The container element
 * @return void
 */
function school_bell_create_hour_section(container) {
	var section = document.createElement('div');
	section.className = 'school_bell_section';
	section.innerHTML = '<h4>Hour</h4>';

	// Hour buttons (0-23)
	var grid = document.createElement('div');
	grid.className = 'school_bell_grid school_bell_hour_grid';
	for (var i = 0; i < 24; i++) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'school_bell_toggle_btn';
		btn.textContent = i < 10 ? '0' + i : i;
		btn.setAttribute('data-value', i);
		btn.onclick = function() {
			this.classList.toggle('active');
			school_bell_update_from_buttons('hour');
		};
		grid.appendChild(btn);
	}
	section.appendChild(grid);

	// Step option
	var step_container = document.createElement('div');
	step_container.className = 'school_bell_step_input';
	step_container.innerHTML = `
		<label>
			<input type="checkbox" id="hour_step_enable">
			Every
		</label>
		<input type="number" id="hour_step_value" min="1" max="23" placeholder="6" disabled>
		<span>hour(s)</span>
	`;
	section.appendChild(step_container);

	container.appendChild(section);

	// Event listeners
	document.getElementById('hour_step_enable').addEventListener('change', function() {
		var step_input = document.getElementById('hour_step_value');
		step_input.disabled = !this.checked;
		if (this.checked) {
			school_bell_deactivate_buttons('hour');
		}
		school_bell_update_from_buttons('hour');
	});

	document.getElementById('hour_step_value').addEventListener('change', function() {
		school_bell_update_from_buttons('hour');
	});
}

/**
 * Creates day of month section
 * @access private
 * @param {HTMLElement} container The container element
 * @return void
 */
function school_bell_create_day_of_month_section(container) {
	var section = document.createElement('div');
	section.className = 'school_bell_section';
	section.innerHTML = '<h4>Day of Month</h4>';

	var grid = document.createElement('div');
	grid.className = 'school_bell_grid school_bell_day_grid school_bell_dom_grid';
	for (var i = 1; i <= 31; i++) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'school_bell_toggle_btn';
		btn.textContent = i;
		btn.setAttribute('data-value', i);
		btn.onclick = function() {
			this.classList.toggle('active');
			school_bell_update_from_buttons('dom');
		};
		grid.appendChild(btn);
	}
	section.appendChild(grid);

	// Step option
	var step_container = document.createElement('div');
	step_container.className = 'school_bell_step_input';
	step_container.innerHTML = `
		<label>
			<input type="checkbox" id="dom_step_enable">
			Every
		</label>
		<input type="number" id="dom_step_value" min="1" max="31" placeholder="5" disabled>
		<span>day(s)</span>
	`;
	section.appendChild(step_container);

	container.appendChild(section);

	// Event listeners
	document.getElementById('dom_step_enable').addEventListener('change', function() {
		var step_input = document.getElementById('dom_step_value');
		step_input.disabled = !this.checked;
		if (this.checked) {
			school_bell_deactivate_buttons('dom');
		}
		school_bell_update_from_buttons('dom');
	});

	document.getElementById('dom_step_value').addEventListener('change', function() {
		school_bell_update_from_buttons('dom');
	});
}

/**
 * Creates month section
 * @access private
 * @param {HTMLElement} container The container element
 * @return void
 */
function school_bell_create_month_section(container) {
	var section = document.createElement('div');
	section.className = 'school_bell_section';
	section.innerHTML = '<h4>Month</h4>';
	
	var grid = document.createElement('div');
	grid.className = 'school_bell_grid school_bell_month_grid school_bell_mon_grid';
	var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
	months.forEach(function(month, index) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'school_bell_toggle_btn';
		btn.textContent = month;
		btn.setAttribute('data-value', index + 1);
		btn.onclick = function() {
			this.classList.toggle('active');
			school_bell_update_from_buttons('mon');
		};
		grid.appendChild(btn);
	});
	section.appendChild(grid);
	
	// Step option
	var step_container = document.createElement('div');
	step_container.className = 'school_bell_step_input';
	step_container.innerHTML = `
		<label>
			<input type="checkbox" id="mon_step_enable">
			Every
		</label>
		<input type="number" id="mon_step_value" min="1" max="12" placeholder="3" disabled>
		<span>month(s)</span>
	`;
	section.appendChild(step_container);
	
	container.appendChild(section);
	
	// Event listeners
	document.getElementById('mon_step_enable').addEventListener('change', function() {
		var step_input = document.getElementById('mon_step_value');
		step_input.disabled = !this.checked;
		if (this.checked) {
			school_bell_deactivate_buttons('mon');
		}
		school_bell_update_from_buttons('mon');
	});
	
	document.getElementById('mon_step_value').addEventListener('change', function() {
		school_bell_update_from_buttons('mon');
	});
}

/**
 * Creates day of week section
 * @access private
 * @param {HTMLElement} container The container element
 * @return void
 */
function school_bell_create_day_of_week_section(container) {
	var section = document.createElement('div');
	section.className = 'school_bell_section';
	section.innerHTML = '<h4>Day of Week</h4>';
	
	var grid = document.createElement('div');
	grid.className = 'school_bell_grid school_bell_week_grid school_bell_dow_grid';
	var days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
	days.forEach(function(day, index) {
		var btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'school_bell_toggle_btn';
		btn.textContent = day;
		btn.setAttribute('data-value', index);
		btn.onclick = function() {
			this.classList.toggle('active');
			school_bell_update_from_buttons('dow');
		};
		grid.appendChild(btn);
	});
	section.appendChild(grid);
	
	container.appendChild(section);
}

/**
 * Deactivates all buttons for a field
 * @access private
 * @param {string} field_type The field type
 * @return void
 */
function school_bell_deactivate_buttons(field_type) {
	var buttons = document.querySelectorAll('.school_bell_' + field_type + '_grid .school_bell_toggle_btn');
	buttons.forEach(function(btn) {
		btn.classList.remove('active');
	});
}

/**
 * Updates hidden field from button selections
 * @access private
 * @param {string} field_type The field type
 * @return void
 */
function school_bell_update_from_buttons(field_type) {
	// Check for step value
	var step_enable = document.getElementById(field_type + '_step_enable');
	if (step_enable && step_enable.checked) {
		var step_value = document.getElementById(field_type + '_step_value');
		if (step_value && step_value.value) {
			school_bell_update_hidden_field(field_type, '*/' + step_value.value);
			school_bell_update_cron_preview_extended();
			return;
		}
	}

	// Get active buttons
	var buttons = document.querySelectorAll('.school_bell_' + field_type + '_grid .school_bell_toggle_btn.active');
	var values = Array.from(buttons).map(function(btn) {
		return btn.getAttribute('data-value');
	}).sort(function(a, b) { return parseInt(a) - parseInt(b); });

	if (values.length === 0) {
		school_bell_update_hidden_field(field_type, '-1');
	} else {
		school_bell_update_hidden_field(field_type, values.join(','));
	}

	school_bell_update_cron_preview_extended();
}

/**
 * Updates hidden field
 * @access private
 * @param {string} field_type The field type
 * @param {string} value The value
 * @return void
 */
function school_bell_update_hidden_field(field_type, value) {
	var hidden = document.getElementById('school_bell_' + field_type);
	if (hidden) {
		hidden.value = value || '-1';
	}
}

/**
 * Updates cron preview display
 * @access private
 * @return void
 */
function school_bell_update_cron_preview() {
	var min = document.getElementById('school_bell_min').value;
	var hour = document.getElementById('school_bell_hour').value;
	var dom = document.getElementById('school_bell_dom').value;
	var mon = document.getElementById('school_bell_mon').value;
	var dow = document.getElementById('school_bell_dow').value;

	// Convert -1 to *
	min = (min === '-1' || min === '') ? '*' : min;
	hour = (hour === '-1' || hour === '') ? '*' : hour;
	dom = (dom === '-1' || dom === '') ? '*' : dom;
	mon = (mon === '-1' || mon === '') ? '*' : mon;
	dow = (dow === '-1' || dow === '') ? '*' : dow;

	var cron_display = min + ' ' + hour + ' ' + dom + ' ' + mon + ' ' + dow;
	document.getElementById('school_bell_cron_display').textContent = cron_display;
}

/**
 * Populates interface from hidden fields
 * @access private
 * @return void
 */
function school_bell_populate_from_hidden() {
	school_bell_populate_field('min');
	school_bell_populate_field('hour');
	school_bell_populate_field('dom');
	school_bell_populate_field('mon');
	school_bell_populate_field('dow');
}

/**
 * Populates a specific field from hidden value
 * @access private
 * @param {string} field_type The field type
 * @return void
 */
function school_bell_populate_field(field_type) {
	var value = document.getElementById('school_bell_' + field_type).value;

	if (!value || value === '-1') {
		return;
	}

	// Handle step values
	if (value.indexOf('*/') === 0) {
		var step = value.substring(2);
		var step_enable = document.getElementById(field_type + '_step_enable');
		var step_value = document.getElementById(field_type + '_step_value');
		if (step_enable && step_value) {
			step_enable.checked = true;
			step_value.disabled = false;
			step_value.value = step;
		}
		return;
	}

	// Handle comma-separated or single values
	var values = value.indexOf(',') > -1 ? value.split(',') : [value];
	values.forEach(function(val) {
		var btn = document.querySelector('.school_bell_' + field_type + '_grid [data-value="' + val + '"]');
		if (btn) {
			btn.classList.add('active');
		}
	});
}

/**
 * Handles scroll to show/hide floating preview in action bar
 * @access private
 * @return void
 */
function school_bell_handle_scroll() {
	var preview = document.querySelector('.school_bell_cron_preview');
	var action_bar = document.getElementById('action_bar');
	var floating = document.getElementById('school_bell_cron_floating');

	if (!preview || !action_bar) {
		return;
	}

	var preview_rect = preview.getBoundingClientRect();
	var action_bar_rect = action_bar.getBoundingClientRect();
	
	// Check if preview top is at or above action bar bottom (meeting point)
	var is_hidden = preview_rect.top <= action_bar_rect.bottom;

	if (is_hidden && !floating) {
		// Create floating preview in action bar
		var cron_value = document.getElementById('school_bell_cron_display').textContent;
		var floating_div = document.createElement('div');
		floating_div.id = 'school_bell_cron_floating';
		floating_div.className = 'school_bell_cron_floating';
		floating_div.innerHTML = '<strong style="color: #2E7D32; margin-right: 8px;">Schedule:</strong><span class="school_bell_cron_value">' + cron_value + '</span>';

		// Insert into action bar actions div
		var actions_div = action_bar.querySelector('.actions');
		if (actions_div) {
			actions_div.insertBefore(floating_div, actions_div.firstChild);
		}
	} else if (!is_hidden && floating) {
		// Remove floating preview
		floating.remove();
	} else if (is_hidden && floating) {
		// Update existing floating preview
		var cron_value = document.getElementById('school_bell_cron_display').textContent;
		var value_span = floating.querySelector('.school_bell_cron_value');
		if (value_span) {
			value_span.textContent = cron_value;
		}
	}
}

/**
 * Updates cron preview display and floating version
 * @access private
 * @return void
 */
function school_bell_update_cron_preview_extended() {
	school_bell_update_cron_preview();

	// Update floating version if it exists
	var floating = document.getElementById('school_bell_cron_floating');
	if (floating) {
		var cron_value = document.getElementById('school_bell_cron_display').textContent;
		var value_span = floating.querySelector('.school_bell_cron_value');
		if (value_span) {
			value_span.textContent = cron_value;
		}
	}
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', function() {
		school_bell_schedule_time_init();
		window.addEventListener('scroll', school_bell_handle_scroll);
	});
} else {
	school_bell_schedule_time_init();
	window.addEventListener('scroll', school_bell_handle_scroll);
}
