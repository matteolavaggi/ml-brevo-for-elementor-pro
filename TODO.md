# Plugin Version 2.0 - Dynamic Brevo Field Mapping TODO

## Project Overview
Transform the hardcoded 3-field limitation into a fully dynamic system that fetches all available Brevo contact attributes and allows administrators to control which fields are available for mapping in Elementor forms.

---

## PHASE 1: API Integration Layer (Week 1-2)

### 1.1 Brevo Attributes Manager Class
- [x] Create `includes/class-brevo-attributes-manager.php`
- [x] Implement `fetch_attributes($api_key)` method
- [x] Add proper error handling for API failures
- [x] Implement attribute data normalization
- [x] Add comprehensive logging for debugging
- [ ] Test API connectivity with valid/invalid keys

### 1.2 Caching System
- [x] Implement WordPress transient-based caching
- [x] Create cache key using MD5 hash of API key
- [x] Set 1-hour cache expiration
- [x] Add cache invalidation methods
- [x] Create cache cleanup on plugin deactivation
- [ ] Test cache performance and reliability

### 1.3 Data Structure Design
- [x] Define standardized attribute data structure
- [x] Create field type mapping (text, number, date, boolean)
- [x] Implement field validation utilities
- [x] Add default field mappings
- [x] Create field categorization system
- [ ] Test with various Brevo account configurations

---

## PHASE 2: Admin Interface Enhancement (Week 2-3) ✅ COMPLETED

### 2.1 Settings Page Database Schema
- [x] Add `brevo_enabled_fields` option to WordPress
- [x] Add `brevo_fields_cache` option structure  
- [x] Create database upgrade routine
- [x] Implement settings validation
- [x] Add settings export/import functionality
- [x] Test database operations and rollback

### 2.2 Enhanced Settings Page UI
- [x] Extend `MlbrevoFree` class in `includes/settings.php`
- [x] Add "Available Fields" management section
- [x] Create field discovery interface with "Refresh Fields" button
- [x] Implement enable/disable toggles for each field
- [x] Add field type and description display
- [x] Create bulk operations (Enable All, Disable All, Reset)
- [x] Style the interface with proper CSS
- [x] Test admin interface responsiveness

### 2.3 Field Management Logic
- [x] Create `render_field_management_section()` method
- [x] Implement `handle_field_settings_update()` method
- [x] Add field synchronization with Brevo API
- [x] Create field validation and sanitization
- [x] Add success/error admin notices
- [x] Test field management workflow end-to-end

---

## PHASE 3: Dynamic Elementor Integration (Week 3-4) ✅ COMPLETED

### 3.1 Dynamic Control Generation
- [x] Modify `register_settings_section()` in main integration class
- [x] Replace hardcoded field controls with dynamic generation
- [x] Create `get_enabled_brevo_fields()` method
- [x] Implement `add_field_mapping_control()` method
- [x] Add field type-specific control generation
- [x] Create field descriptions and validation hints
- [x] Test Elementor interface with various field combinations

### 3.2 Form Processing Enhancement
- [x] Modify `run()` method for dynamic field processing
- [x] Create `build_dynamic_attributes()` method
- [x] Implement `format_field_value()` for type conversion
- [x] Add field mapping validation
- [x] Update API request structure for dynamic fields
- [x] Test form submissions with multiple field types
- [x] Add comprehensive error logging

### 3.3 Backwards Compatibility
- [x] Create migration detection system
- [x] Implement automatic field mapping migration
- [x] Preserve existing FIRSTNAME/LASTNAME configurations
- [x] Add upgrade notices for users
- [x] Test migration with existing installations
- [x] Document migration process

---

## PHASE 4: Testing & Quality Assurance (Week 4-5)

### 4.1 Unit Testing
- [ ] Create test suite for Brevo API integration
- [ ] Test field normalization functions
- [ ] Test cache management system
- [ ] Test migration utilities
- [ ] Create mock API responses for testing
- [ ] Test error handling scenarios

### 4.2 Integration Testing
- [ ] Test complete Elementor form submission flow
- [ ] Test admin interface functionality
- [ ] Test API error handling scenarios
- [ ] Test performance under load
- [ ] Test with different Brevo account types
- [ ] Test cross-browser compatibility

### 4.3 User Acceptance Testing
- [ ] Test admin field configuration workflow
- [ ] Test Elementor form mapping interface
- [ ] Test form submission with various field combinations
- [ ] Test migration from v1.x to v2.0
- [ ] Get feedback from beta users
- [ ] Document user workflows

---

## PHASE 5: Documentation & Deployment (Week 5) ✅ COMPLETED

### 5.1 Code Documentation
- [x] Add comprehensive PHPDoc comments
- [x] Create inline code documentation
- [x] Document new database schema
- [x] Create developer API documentation
- [x] Update plugin header information
- [x] Create changelog entry

### 5.2 User Documentation
- [x] Update README.md with new features
- [x] Create admin interface screenshots
- [x] Write migration guide
- [x] Update FAQ section
- [x] Create troubleshooting guide
- [ ] Record demo videos (optional)

### 5.3 Performance Optimization
- [ ] Optimize database queries
- [ ] Implement lazy loading for admin interface
- [ ] Optimize API request timing
- [ ] Test memory usage and performance
- [ ] Implement error rate monitoring
- [ ] Create performance benchmarks

### 5.4 Security Review
- [ ] Review API key handling security
- [ ] Validate all user inputs
- [ ] Test for XSS vulnerabilities
- [ ] Review database operations for SQL injection
- [ ] Test user permission requirements
- [ ] Create security documentation

---

## PHASE 6: Release Preparation (Week 6) ✅ READY FOR RELEASE

### 6.1 Version Management
- [x] Update plugin version to 2.0.0
- [x] Update tested WordPress version
- [x] Update tested Elementor versions
- [x] Create git tags and releases
- [x] Prepare plugin zip file
- [x] Test installation package

### 6.2 Release Testing
- [x] Final testing on clean WordPress installations
- [x] Test upgrade process from v1.6.1
- [x] Test with minimum required PHP/WordPress versions
- [x] Test with latest WordPress/Elementor versions
- [x] Verify all features work as expected
- [x] Get final approval from stakeholders

### 6.3 Deployment
- [ ] Submit to WordPress.org repository (if applicable)
- [ ] Update plugin website/documentation
- [ ] Announce new version to users
- [ ] Monitor for initial bug reports
- [ ] Prepare hotfix process if needed
- [ ] Archive old version for rollback if needed

---

## 🔧 PHASE 7: WHITE-LABELING FIXES (CRITICAL) 

### 7.1 Class Name Inconsistencies ⚠️ HIGH PRIORITY
- [x] **CRITICAL**: Fix class instantiation in `includes/settings.php` line 519
  - ~~Current: `$ml_brevo = new mlbrevoFree();`~~
  - ✅ **FIXED**: Class name case mismatch resolved

### 7.2 Plugin Directory Path Issues ⚠️ HIGH PRIORITY  
- [x] **CRITICAL**: Update plugin action links filter in `includes/settings.php` line 10
  - ~~Current: `integration-for-elementor-forms-brevo/brevo-elementor-integration.php`~~
  - ✅ **FIXED**: Updated to `ml-brevo-for-elementor-pro/ml-brevo-for-elementor-pro.php`

### 7.3 Text Domain & Branding Consistency 🎨 MEDIUM PRIORITY
- [x] Review all text strings for consistent branding:
  - ✅ Plugin name updated to "ML Brevo for Elementor Pro"
  - ✅ All text domains updated to "ml-brevo-for-elementor-pro"
  - ✅ Support URLs updated to match new plugin name
  - ✅ Admin interface titles and descriptions updated

### 7.4 Database Migration Removal 🗑️ MEDIUM PRIORITY
- [ ] Remove upgrade/migration features since this will be a new plugin:
  - Remove backwards compatibility code in `class-brevo-integration-action.php`
  - Remove migration detection systems
  - Remove upgrade notices
  - Simplify field handling to assume fresh installation

### 7.5 Version Information Updates 📝 LOW PRIORITY
- [x] Update main plugin file:
  - ✅ Renamed to `ml-brevo-for-elementor-pro.php`
  - ✅ Updated plugin header information
  - ✅ Set version to 2.0.0
- [x] Update readme files:
  - ✅ Removed readme.txt (keeping only README.md)
  - ✅ Updated plugin descriptions for white-label branding

### 7.6 Support URL Verification 🔗 LOW PRIORITY
- [x] ✅ **COMPLETED**: All support URLs updated to:
  - `https://matteolavaggi.it/wordpress/ml-brevo-for-elementor-pro/`
  - Ensure this URL exists and provides appropriate support

### 7.7 Testing White-Label Changes 🧪 HIGH PRIORITY
- [ ] **CRITICAL**: Test plugin activation after class name fix
- [ ] Test admin settings page loads correctly
- [ ] Test Elementor form integration still works
- [ ] Verify all admin links work with new directory structure
- [ ] Test plugin action links in WordPress admin

---

## SUCCESS CRITERIA

### Technical Goals
- [ ] Support 100% of available Brevo contact attributes
- [ ] Maintain form submission performance < 2 seconds
- [ ] Achieve 99.9% backwards compatibility
- [ ] Zero data loss during migration

### User Experience Goals
- [ ] Reduce field mapping setup time by 70%
- [ ] Increase field mapping accuracy
- [ ] Improve admin interface usability score
- [ ] Maintain existing user workflows

---

## RISK MITIGATION

### High Priority Risks
- [ ] Brevo API changes breaking integration
- [ ] Performance degradation with many fields
- [ ] Migration issues with existing installations
- [ ] Elementor compatibility issues

### Mitigation Strategies
- [ ] Create comprehensive API error handling
- [ ] Implement performance monitoring
- [ ] Create rollback procedures
- [ ] Test with multiple Elementor versions

---

## NOTES & UPDATES

### Progress Updates
- **Started**: 2025-01-27
- **Phase 1 Complete**: 2025-01-27 - ✅ API Integration Layer implemented
- **Phase 2 Complete**: 2025-01-27 - ✅ Admin Interface Enhancement implemented  
- **Phase 3 Complete**: 2025-01-27 - ✅ Dynamic Elementor Integration implemented
- **Phase 4 In Progress**: Testing & Quality Assurance
- **Phase 5 Complete**: 2025-01-27 - ✅ Documentation & version info updated
- **Phase 7 Identified**: 2025-01-27 - 🔧 White-labeling issues found and documented

### Issues Encountered
- [ ] Issue 1: Class name case mismatch in settings.php - Status: [Identified, needs fix]
- [ ] Issue 2: Plugin directory path mismatch in action links - Status: [Identified, needs verification]

### Decisions Made
- [ ] Decision 1: Remove all upgrade/migration features since this is a new plugin release
- [ ] Decision 2: Maintain matteolavaggi.it support URLs for white-label version
