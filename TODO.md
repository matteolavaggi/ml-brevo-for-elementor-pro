# TODO: Brevo Plugin Enhancement Plan

## 🎯 Main Goals

### 1. Brevo Lists Management
- Fetch and display all Brevo lists in admin settings
- Allow users to select/configure lists for Elementor forms
- Integrate list selection into Elementor form widget (if possible)
- Fallback to settings-based list ID configuration

### 2. Debug System Implementation
- Add debug toggle in main config
- Implement local file logging system within plugin folder
- Create debug log viewer in admin interface
- Add sortable/filterable log display

---

## 📋 Implementation Plan

### Phase 1: Brevo Lists Management 🔄 **IN PROGRESS**

#### Step 1.1: Brevo Lists API Integration ✅ **COMPLETED**
- [x] Create `fetch_lists()` method in `Brevo_Attributes_Manager`
- [x] Add API endpoint for retrieving lists (`/contacts/lists`)
- [x] Implement caching for lists (similar to attributes)
- [x] Add validation and error handling

#### Step 1.2: Admin Interface for Lists ✅ **COMPLETED**
- [x] Add lists management section to settings page
- [x] Display lists in a table with ID, name, folder info
- [x] Add refresh lists functionality
- [x] Show list statistics (subscriber count, etc.)

#### Step 1.3: List Selection Configuration ✅ **COMPLETED**
- [x] Add list selection options to settings
- [x] Allow multiple list selection
- [x] Store selected lists in WordPress options
- [x] Add default list configuration

#### Step 1.4: Elementor Integration Research 🔄 **IN PROGRESS**
- [ ] Research Elementor Pro form action hooks
- [ ] Check if we can add dynamic list selection to form widget
- [ ] Implement Elementor integration if possible
- [ ] Create fallback settings-based approach

### Phase 2: Debug System Implementation ✅ **COMPLETED**

#### Step 2.1: Debug Logger Class ✅ **COMPLETED**
- [x] Create `Brevo_Debug_Logger` class
- [x] Implement file-based logging in plugin directory
- [x] Add log levels (INFO, WARNING, ERROR, DEBUG)
- [x] Add automatic log rotation/cleanup

#### Step 2.2: Debug Settings Interface ✅ **COMPLETED**
- [x] Add debug toggle to main settings
- [x] Create dedicated debug settings page
- [x] Add log level configuration
- [x] Add log retention settings

#### Step 2.3: Debug Log Viewer ✅ **COMPLETED**
- [x] Create admin page for viewing logs
- [x] Implement sortable/filterable log table
- [x] Add search functionality
- [x] Add log export/download feature

#### Step 2.4: Integration with Existing Code ✅ **COMPLETED**
- [x] Add debug logging to all API calls
- [x] Log form submissions and processing
- [x] Log cache operations
- [x] Log error conditions and recovery

---

## 🔧 Technical Implementation Details

### Brevo Lists API Structure
```php
// Expected API response structure
{
  "lists": [
    {
      "id": 123,
      "name": "Newsletter Subscribers",
      "folderIds": [1, 2],
      "totalBlacklisted": 0,
      "totalSubscribers": 1500,
      "uniqueSubscribers": 1450,
      "campaignStats": {...}
    }
  ],
  "count": 10
}
```

### Debug Log Structure
```php
// Log entry format
{
  "timestamp": "2024-01-15 10:30:45",
  "level": "INFO",
  "component": "API",
  "action": "fetch_attributes",
  "message": "Successfully fetched 27 attributes",
  "context": {
    "api_key_hash": "abc123...",
    "response_code": 200,
    "execution_time": 0.45
  }
}
```

---

## 📁 Files to Create/Modify

### New Files
- [ ] `includes/class-brevo-debug-logger.php` - Debug logging system
- [ ] `includes/debug-settings.php` - Debug configuration page
- [ ] `includes/debug-viewer.php` - Log viewing interface
- [ ] `logs/` directory - For storing debug logs

### Files to Modify
- [x] `includes/class-brevo-attributes-manager.php` - Add lists functionality
- [x] `includes/settings.php` - Add lists management UI
- [ ] `includes/class-brevo-integration-action.php` - Add list selection logic
- [ ] `ml-brevo-for-elementor-pro.php` - Register new classes and hooks

---

## 🧪 Testing Checklist

### Lists Management Testing
- [ ] Test lists fetching with valid API key
- [ ] Test error handling with invalid API key
- [ ] Test cache functionality for lists
- [ ] Test list selection and saving
- [ ] Test Elementor integration (if implemented)

### Debug System Testing
- [ ] Test debug toggle functionality
- [ ] Test log file creation and writing
- [ ] Test log viewer interface
- [ ] Test log filtering and sorting
- [ ] Test log rotation and cleanup

---

## 🚀 Deployment Notes

### Database Changes
- New options to store:
  - `brevo_selected_lists` - Array of selected list IDs
  - `brevo_debug_enabled` - Boolean for debug toggle
  - `brevo_debug_level` - Debug level setting
  - `brevo_debug_retention` - Log retention period

### File System Changes
- Create `logs/` directory in plugin folder
- Ensure proper file permissions for log writing
- Add `.htaccess` to protect log files from direct access

---

## 📝 Current Status

**Last Updated**: 2024-01-15
**Current Phase**: Phase 1 & 2 Complete - Moving to Elementor Integration
**Next Action**: Research and implement Elementor form integration

### Completed ✅
- Brevo Lists API Integration
- Admin Interface for Lists Display
- List Selection Configuration
- Debug System Implementation (Complete)

### In Progress 🔄
- Elementor Integration Research

### Pending ⏳
- Testing and Documentation 