# School Bell Time Picker

Modern cron-style scheduling interface for the School Bells application.

## Overview

The time picker provides an intuitive, visual interface for creating cron-style schedules. Users can select times and dates by clicking toggle buttons in a grid layout, with instant visual feedback and a live cron preview.

## Features

- **Toggle Button Interface**: Click buttons to select/deselect (green = selected)
- **Grid Layouts**: Compact, organized grids for all scheduling options
- **Step Functionality**: "Every N" options for minutes, hours, days, and months
- **Live Cron Preview**: Real-time display of resulting cron format
- **Floating Preview**: Moves to action bar when scrolled out of view
- **Responsive Design**: 60% width on desktop, 100% on mobile devices
- **No External Dependencies**: Pure HTML5 and vanilla JavaScript
- **Security**: All values validated and sanitized to prevent injection

## User Interface

### Time Selection

**Minute Section:**
- 60 toggle buttons (00-59) in 12-column grid
- Step option: "Every N minute(s)" with number input
- Examples: Every 15 minutes = `*/15`, Specific minute 30 = `30`

**Hour Section:**
- 24 toggle buttons (00-23) in 12-column grid
- Step option: "Every N hour(s)" with number input
- Examples: Every 6 hours = `*/6`, Specific hour 14 = `14`

### Date Selection

**Day of Month Section:**
- 31 toggle buttons (1-31) in 7-column grid
- Step option: "Every N day(s)" with number input
- Examples: Every 5 days = `*/5`, Days 1,15,30 = `1,15,30`

**Month Section:**
- 12 toggle buttons (Jan-Dec) in 6-column grid
- Step option: "Every N month(s)" with number input
- Examples: Every 3 months = `*/3`, Jan,Jun,Dec = `1,6,12`

**Day of Week Section:**
- 7 toggle buttons (Sun-Sat) in 7-column grid
- Examples: Mon,Tue,Wed = `1,2,3`, All days = `*`

## Cron Preview Display

The cron preview shows the resulting schedule format in real-time:

- **Static Display**: Green box at top of form
- **Floating Display**: Automatically appears in action bar when scrolled
- **Format**: `Minute Hour DayOfMonth Month DayOfWeek`
- **Examples**:
  - `30 14 * * *` = Every day at 14:30
  - `*/15 * * * *` = Every 15 minutes
  - `0 9 1,15 * 1,2,3,4,5` = 9:00 AM on 1st and 15th, weekdays only
  - `0 8 * */3 *` = 8:00 AM every 3 months

## PHP Helper Function

### school_bell_schedule_time()

Formats schedule times for display in the list view.

**Location:** `app/school_bells/resources/functions/school_bell_schedule_time.php`

**Usage:**
```php
require_once "resources/functions/school_bell_schedule_time.php";
echo school_bell_schedule_time($row, 'minutes');
```

**Parameters:**
- `$row` (array): Database row with schedule fields
- `$output_format` (string): Format type (default: 'minutes')
  - `'minutes'` - Default cron format
  - `'cron'` - Full cron format
  - `'hours'` - Hour-based format
  - `'seconds'` - Seconds format with 00 prefix
  - `'timestamp'` - Unix timestamp (when all fields specific)

**Return:**
- Sanitized cron format string (e.g., `"30 14 * * 1,2,3"`)

## File Structure

```
app/school_bells/
├── resources/
│   ├── javascript/
│   │   └── school_bell_time_picker.js    # Main time picker interface
│   ├── functions/
│   │   └── school_bell_schedule_time.php # Display helper function
│   └── classes/
│       ├── school_bell_selector.php      # Original selector (legacy)
│       └── school_bells.php              # School bells class
├── school_bell_edit.php                  # Edit form (updated)
└── school_bells.php                      # List view (updated)
```

## Security

### Input Validation

All cron field values are sanitized by `school_bell_sanitize_cron_field()`:

- **Wildcard values**: `-1` or empty → stored as `-1` (represents `*`)
- **Step values**: `*/15` → validated (step 1-max), invalid → `-1`
- **Comma-separated**: `1,15,30` → each value validated against min/max
- **Single values**: `14` → validated against range
- **XSS Prevention**: All output escaped using `escape()` function
- **SQL Injection Prevention**: Parameterized queries only

### Validation Ranges

- **Minutes**: 0-59
- **Hours**: 0-23
- **Day of Month**: 1-31
- **Month**: 1-12
- **Day of Week**: 0-6 (0=Sunday)

## Technical Details

### JavaScript Functions

**Initialization:**
- `school_bell_schedule_time_init()` - Main initialization
- `school_bell_inject_styles()` - Injects CSS styles

**UI Creation:**
- `school_bell_create_cron_preview()` - Creates preview display
- `school_bell_create_minute_section()` - Creates minute buttons
- `school_bell_create_hour_section()` - Creates hour buttons
- `school_bell_create_day_of_month_section()` - Creates day buttons
- `school_bell_create_month_section()` - Creates month buttons
- `school_bell_create_day_of_week_section()` - Creates weekday buttons

**Data Management:**
- `school_bell_update_from_buttons()` - Updates hidden fields from UI
- `school_bell_update_hidden_field()` - Sets hidden field value
- `school_bell_populate_from_hidden()` - Populates UI from database
- `school_bell_populate_field()` - Populates specific field

**Preview Functions:**
- `school_bell_update_cron_preview()` - Updates main preview
- `school_bell_update_cron_preview_extended()` - Updates both previews
- `school_bell_handle_scroll()` - Manages floating preview

### Cron Format Support

**Supported Formats:**
- **Wildcard**: `*` (any/all values)
- **Single Value**: `14` (specific value)
- **Multiple Values**: `1,15,30` (comma-separated list)
- **Step Values**: `*/15` (every N units)

**Format Combinations:**
- `30 14 * * *` - Daily at 14:30
- `*/15 * * * *` - Every 15 minutes
- `0 9 1,15 * *` - 9:00 AM on 1st and 15th of month
- `0 8 * * 1,2,3,4,5` - 8:00 AM on weekdays
- `0 */6 * * *` - Every 6 hours at minute 0
- `0 0 */5 * *` - Midnight every 5 days
- `0 0 1 */3 *` - First day of every 3 months

## Browser Compatibility

- **Modern Browsers**: Full CSS Grid support
- **Mobile Devices**: Responsive design (100% width)
- **JavaScript Required**: Interface requires JavaScript enabled
- **Fallback**: Hidden fields still function if JavaScript fails

## Usage Guide

### Creating a Schedule

1. Click the toggle buttons for the desired values (green = selected)
2. Use step checkboxes for recurring intervals
3. Watch the cron preview update in real-time
4. Scroll down - preview automatically appears in action bar
5. Click Save to store the schedule

### Common Scheduling Examples

**School Bell at 8:30 AM on weekdays:**
- Minute: 30
- Hour: 08
- Day of Month: (none - leave all unselected)
- Month: (none - leave all unselected)
- Day of Week: Mon, Tue, Wed, Thu, Fri
- Result: `30 8 * * 1,2,3,4,5`

**Hourly Bell Every School Day:**
- Minute: 00
- Hour: Click "Every N hour(s)" checkbox, enter 1
- Day of Month: (none)
- Month: (none)
- Day of Week: Mon-Fri
- Result: `0 */1 * * 1,2,3,4,5`

**End of Month Bell:**
- Minute: 00
- Hour: 15
- Day of Month: 30, 31
- Month: (none)
- Day of Week: (none)
- Result: `0 15 30,31 * *`

## Advantages Over Dropdown Selectors

- **Visual Selection**: See all options at once
- **Multi-Select**: Click multiple values easily
- **Toggle Interface**: Click again to deselect
- **Space Efficient**: Grid layout uses minimal space
- **No Typing**: No need to enter numbers manually
- **Instant Feedback**: Green buttons show selections clearly
- **Live Preview**: See cron result immediately
- **Always Visible**: Preview follows you when scrolling

## Development Notes

### Following FusionPBX Standards

- **Naming Convention**: All functions use `snake_case`
- **PHPDocs**: All functions documented with `@access` and `@return` tags
- **Security First**: Validation before database operations
- **No Dependencies**: Uses only included libraries
- **Project Format**: Code style matches existing FusionPBX structure

### Code Quality

- **Beginner Friendly**: Clear function names and documentation
- **Maintainable**: Well-organized, modular code structure
- **Secure**: Input validation and output escaping throughout
- **Tested**: Works with existing cron job execution

## Troubleshooting

**Buttons not appearing:**
- Check JavaScript console for errors
- Ensure `school_bell_schedule_time_picker` div exists
- Verify JavaScript file path is correct

**Preview not updating:**
- Ensure grid class names include field type (e.g., `school_bell_mon_grid`)
- Check that hidden fields exist with proper IDs
- Verify event handlers are attached

**Floating preview not showing:**
- Check that `action_bar` div exists on page
- Verify scroll event listener is registered
- Ensure action bar has `.actions` child element

**Values not saving:**
- Confirm sanitization function `school_bell_sanitize_cron_field()` exists
- Check hidden fields have proper names (`school_bell_min`, etc.)
- Verify POST data is being processed

## Support

For issues or questions about the time picker implementation, refer to:
- Main School Bells README: `app/school_bells/README.md`
- Source Code: `app/school_bells/resources/javascript/school_bell_time_picker.js`
- Helper Function: `app/school_bells/resources/functions/school_bell_schedule_time.php`
