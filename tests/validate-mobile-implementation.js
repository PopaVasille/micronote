/**
 * Mobile Implementation Validation Script
 * 
 * This script validates that the mobile functionality has been properly implemented
 * according to the requirements and design specifications.
 */

const fs = require('fs');
const path = require('path');

// File paths to validate
const FILES_TO_CHECK = {
  unifiedCard: 'resources/js/Components/Dashboard/UnifiedTasksRemindersCard.vue',
  activeTasksSubsection: 'resources/js/Components/Dashboard/ActiveTasksSubsection.vue',
  remindersSubsection: 'resources/js/Components/Dashboard/RemindersSubsection.vue',
  dashboard: 'resources/js/Pages/Dashboard.vue',
  enTranslations: 'resources/js/locales/en.json',
  roTranslations: 'resources/js/locales/ro.json'
};

// Mobile-specific patterns to check
const MOBILE_PATTERNS = {
  touchTargets: /min-height:\s*44px|min-width:\s*44px|w-11\s+h-11/g,
  touchManipulation: /touch-manipulation/g,
  mobileSpacing: /px-3|py-3|p-3|space-y-3|space-y-4/g,
  responsiveText: /text-sm|text-xs|text-base/g,
  mobileBreakpoints: /sm:hidden|hidden\s+sm:inline|sm:inline/g,
  activeStates: /active:scale-\[0\.98\]|active:scale-95|active:bg-/g,
  loadingStates: /animate-pulse|animate-spin/g,
  errorHandling: /bg-red-50|border-red-200|text-red-700/g,
  ariaLabels: /aria-live|sr-only|aria-label/g
};

// Translation keys that should exist for mobile
const REQUIRED_MOBILE_TRANSLATIONS = [
  'dashboard.unified_section.title_full',
  'dashboard.unified_section.title_mobile',
  'dashboard.unified_section.active_tasks',
  'dashboard.unified_section.no_due_date',
  'dashboard.unified_section.completing',
  'dashboard.unified_section.mark_as_completed',
  'dashboard.unified_section.retry',
  'dashboard.unified_section.retrying'
];

/**
 * Validation Results
 */
const validationResults = {
  touchInteractions: {
    passed: 0,
    failed: 0,
    issues: []
  },
  responsiveDesign: {
    passed: 0,
    failed: 0,
    issues: []
  },
  loadingStates: {
    passed: 0,
    failed: 0,
    issues: []
  },
  visualFeedback: {
    passed: 0,
    failed: 0,
    issues: []
  },
  accessibility: {
    passed: 0,
    failed: 0,
    issues: []
  },
  translations: {
    passed: 0,
    failed: 0,
    issues: []
  }
};

/**
 * Validate Touch Interactions
 */
function validateTouchInteractions() {
  console.log('🔍 Validating Touch Interactions...');
  
  const filesToCheck = [
    FILES_TO_CHECK.activeTasksSubsection,
    FILES_TO_CHECK.remindersSubsection
  ];
  
  filesToCheck.forEach(filePath => {
    try {
      const content = fs.readFileSync(filePath, 'utf8');
      
      // Check for proper touch targets
      const touchTargetMatches = content.match(MOBILE_PATTERNS.touchTargets);
      if (touchTargetMatches && touchTargetMatches.length > 0) {
        validationResults.touchInteractions.passed++;
        console.log(`✅ ${path.basename(filePath)}: Touch targets properly sized`);
      } else {
        validationResults.touchInteractions.failed++;
        validationResults.touchInteractions.issues.push(
          `${path.basename(filePath)}: Missing proper touch target sizing (44px minimum)`
        );
      }
      
      // Check for touch manipulation
      const touchManipulationMatches = content.match(MOBILE_PATTERNS.touchManipulation);
      if (touchManipulationMatches && touchManipulationMatches.length > 0) {
        validationResults.touchInteractions.passed++;
        console.log(`✅ ${path.basename(filePath)}: Touch manipulation properly configured`);
      } else {
        validationResults.touchInteractions.failed++;
        validationResults.touchInteractions.issues.push(
          `${path.basename(filePath)}: Missing touch-manipulation class`
        );
      }
      
      // Check for active states
      const activeStateMatches = content.match(MOBILE_PATTERNS.activeStates);
      if (activeStateMatches && activeStateMatches.length > 0) {
        validationResults.touchInteractions.passed++;
        console.log(`✅ ${path.basename(filePath)}: Active states implemented for touch feedback`);
      } else {
        validationResults.touchInteractions.failed++;
        validationResults.touchInteractions.issues.push(
          `${path.basename(filePath)}: Missing active states for touch feedback`
        );
      }
      
    } catch (error) {
      validationResults.touchInteractions.failed++;
      validationResults.touchInteractions.issues.push(
        `${path.basename(filePath)}: File not found or readable`
      );
    }
  });
}

/**
 * Validate Responsive Design
 */
function validateResponsiveDesign() {
  console.log('📱 Validating Responsive Design...');
  
  const filesToCheck = [
    FILES_TO_CHECK.unifiedCard,
    FILES_TO_CHECK.activeTasksSubsection,
    FILES_TO_CHECK.remindersSubsection
  ];
  
  filesToCheck.forEach(filePath => {
    try {
      const content = fs.readFileSync(filePath, 'utf8');
      
      // Check for mobile spacing
      const mobileSpacingMatches = content.match(MOBILE_PATTERNS.mobileSpacing);
      if (mobileSpacingMatches && mobileSpacingMatches.length > 0) {
        validationResults.responsiveDesign.passed++;
        console.log(`✅ ${path.basename(filePath)}: Mobile-first spacing implemented`);
      } else {
        validationResults.responsiveDesign.failed++;
        validationResults.responsiveDesign.issues.push(
          `${path.basename(filePath)}: Missing mobile-first spacing (px-3, py-3, etc.)`
        );
      }
      
      // Check for responsive text
      const responsiveTextMatches = content.match(MOBILE_PATTERNS.responsiveText);
      if (responsiveTextMatches && responsiveTextMatches.length > 0) {
        validationResults.responsiveDesign.passed++;
        console.log(`✅ ${path.basename(filePath)}: Responsive text sizing implemented`);
      } else {
        validationResults.responsiveDesign.failed++;
        validationResults.responsiveDesign.issues.push(
          `${path.basename(filePath)}: Missing responsive text sizing`
        );
      }
      
      // Check for mobile breakpoints
      const breakpointMatches = content.match(MOBILE_PATTERNS.mobileBreakpoints);
      if (breakpointMatches && breakpointMatches.length > 0) {
        validationResults.responsiveDesign.passed++;
        console.log(`✅ ${path.basename(filePath)}: Mobile breakpoints properly configured`);
      } else {
        validationResults.responsiveDesign.failed++;
        validationResults.responsiveDesign.issues.push(
          `${path.basename(filePath)}: Missing mobile breakpoint configurations`
        );
      }
      
    } catch (error) {
      validationResults.responsiveDesign.failed++;
      validationResults.responsiveDesign.issues.push(
        `${path.basename(filePath)}: File not found or readable`
      );
    }
  });
}

/**
 * Validate Loading States
 */
function validateLoadingStates() {
  console.log('⏳ Validating Loading States...');
  
  const filesToCheck = [
    FILES_TO_CHECK.activeTasksSubsection,
    FILES_TO_CHECK.remindersSubsection
  ];
  
  filesToCheck.forEach(filePath => {
    try {
      const content = fs.readFileSync(filePath, 'utf8');
      
      // Check for loading animations
      const loadingMatches = content.match(MOBILE_PATTERNS.loadingStates);
      if (loadingMatches && loadingMatches.length > 0) {
        validationResults.loadingStates.passed++;
        console.log(`✅ ${path.basename(filePath)}: Loading states implemented`);
      } else {
        validationResults.loadingStates.failed++;
        validationResults.loadingStates.issues.push(
          `${path.basename(filePath)}: Missing loading state animations`
        );
      }
      
      // Check for error handling
      const errorMatches = content.match(MOBILE_PATTERNS.errorHandling);
      if (errorMatches && errorMatches.length > 0) {
        validationResults.loadingStates.passed++;
        console.log(`✅ ${path.basename(filePath)}: Error states implemented`);
      } else {
        validationResults.loadingStates.failed++;
        validationResults.loadingStates.issues.push(
          `${path.basename(filePath)}: Missing error state styling`
        );
      }
      
      // Check for retry functionality
      if (content.includes('retryFetch') || content.includes('retry')) {
        validationResults.loadingStates.passed++;
        console.log(`✅ ${path.basename(filePath)}: Retry functionality implemented`);
      } else {
        validationResults.loadingStates.failed++;
        validationResults.loadingStates.issues.push(
          `${path.basename(filePath)}: Missing retry functionality`
        );
      }
      
    } catch (error) {
      validationResults.loadingStates.failed++;
      validationResults.loadingStates.issues.push(
        `${path.basename(filePath)}: File not found or readable`
      );
    }
  });
}

/**
 * Validate Visual Feedback
 */
function validateVisualFeedback() {
  console.log('✨ Validating Visual Feedback...');
  
  const filesToCheck = [
    FILES_TO_CHECK.activeTasksSubsection,
    FILES_TO_CHECK.remindersSubsection
  ];
  
  filesToCheck.forEach(filePath => {
    try {
      const content = fs.readFileSync(filePath, 'utf8');
      
      // Check for hover states
      if (content.includes('hover:') && content.includes('@media (hover: hover)')) {
        validationResults.visualFeedback.passed++;
        console.log(`✅ ${path.basename(filePath)}: Conditional hover states implemented`);
      } else {
        validationResults.visualFeedback.failed++;
        validationResults.visualFeedback.issues.push(
          `${path.basename(filePath)}: Missing conditional hover states for touch devices`
        );
      }
      
      // Check for transitions
      if (content.includes('transition-') || content.includes('duration-')) {
        validationResults.visualFeedback.passed++;
        console.log(`✅ ${path.basename(filePath)}: Smooth transitions implemented`);
      } else {
        validationResults.visualFeedback.failed++;
        validationResults.visualFeedback.issues.push(
          `${path.basename(filePath)}: Missing smooth transitions`
        );
      }
      
      // Check for priority indicators
      if (content.includes('🔥') && content.includes('⚡') && content.includes('▫️')) {
        validationResults.visualFeedback.passed++;
        console.log(`✅ ${path.basename(filePath)}: Priority indicators implemented`);
      } else {
        validationResults.visualFeedback.failed++;
        validationResults.visualFeedback.issues.push(
          `${path.basename(filePath)}: Missing priority indicators`
        );
      }
      
    } catch (error) {
      validationResults.visualFeedback.failed++;
      validationResults.visualFeedback.issues.push(
        `${path.basename(filePath)}: File not found or readable`
      );
    }
  });
}

/**
 * Validate Accessibility
 */
function validateAccessibility() {
  console.log('♿ Validating Accessibility...');
  
  const filesToCheck = [
    FILES_TO_CHECK.activeTasksSubsection,
    FILES_TO_CHECK.remindersSubsection
  ];
  
  filesToCheck.forEach(filePath => {
    try {
      const content = fs.readFileSync(filePath, 'utf8');
      
      // Check for ARIA labels
      const ariaMatches = content.match(MOBILE_PATTERNS.ariaLabels);
      if (ariaMatches && ariaMatches.length > 0) {
        validationResults.accessibility.passed++;
        console.log(`✅ ${path.basename(filePath)}: ARIA labels and screen reader support implemented`);
      } else {
        validationResults.accessibility.failed++;
        validationResults.accessibility.issues.push(
          `${path.basename(filePath)}: Missing ARIA labels and screen reader support`
        );
      }
      
      // Check for semantic HTML
      if (content.includes('<h2>') || content.includes('<h3>') || content.includes('<button>')) {
        validationResults.accessibility.passed++;
        console.log(`✅ ${path.basename(filePath)}: Semantic HTML structure implemented`);
      } else {
        validationResults.accessibility.failed++;
        validationResults.accessibility.issues.push(
          `${path.basename(filePath)}: Missing semantic HTML structure`
        );
      }
      
      // Check for focus management
      if (content.includes('focus:') || content.includes('focus-visible:')) {
        validationResults.accessibility.passed++;
        console.log(`✅ ${path.basename(filePath)}: Focus management implemented`);
      } else {
        validationResults.accessibility.failed++;
        validationResults.accessibility.issues.push(
          `${path.basename(filePath)}: Missing focus management`
        );
      }
      
    } catch (error) {
      validationResults.accessibility.failed++;
      validationResults.accessibility.issues.push(
        `${path.basename(filePath)}: File not found or readable`
      );
    }
  });
}

/**
 * Validate Translations
 */
function validateTranslations() {
  console.log('🌐 Validating Translations...');
  
  const translationFiles = [
    FILES_TO_CHECK.enTranslations,
    FILES_TO_CHECK.roTranslations
  ];
  
  translationFiles.forEach(filePath => {
    try {
      const content = fs.readFileSync(filePath, 'utf8');
      const translations = JSON.parse(content);
      
      let missingKeys = [];
      REQUIRED_MOBILE_TRANSLATIONS.forEach(key => {
        const keyParts = key.split('.');
        let current = translations;
        
        for (const part of keyParts) {
          if (current && typeof current === 'object' && current.hasOwnProperty(part)) {
            current = current[part];
          } else {
            missingKeys.push(key);
            break;
          }
        }
      });
      
      if (missingKeys.length === 0) {
        validationResults.translations.passed++;
        console.log(`✅ ${path.basename(filePath)}: All required mobile translations present`);
      } else {
        validationResults.translations.failed++;
        validationResults.translations.issues.push(
          `${path.basename(filePath)}: Missing translations: ${missingKeys.join(', ')}`
        );
      }
      
    } catch (error) {
      validationResults.translations.failed++;
      validationResults.translations.issues.push(
        `${path.basename(filePath)}: File not found or not valid JSON`
      );
    }
  });
}

/**
 * Generate Validation Report
 */
function generateValidationReport() {
  console.log('\n📊 Mobile Implementation Validation Report');
  console.log('='.repeat(50));
  
  const categories = Object.keys(validationResults);
  let totalPassed = 0;
  let totalFailed = 0;
  
  categories.forEach(category => {
    const result = validationResults[category];
    totalPassed += result.passed;
    totalFailed += result.failed;
    
    const status = result.failed === 0 ? '✅ PASSED' : '❌ FAILED';
    console.log(`\n${category.toUpperCase()}: ${status}`);
    console.log(`  Passed: ${result.passed}`);
    console.log(`  Failed: ${result.failed}`);
    
    if (result.issues.length > 0) {
      console.log('  Issues:');
      result.issues.forEach(issue => {
        console.log(`    - ${issue}`);
      });
    }
  });
  
  console.log('\n' + '='.repeat(50));
  console.log(`OVERALL RESULT: ${totalFailed === 0 ? '✅ ALL TESTS PASSED' : '❌ SOME TESTS FAILED'}`);
  console.log(`Total Passed: ${totalPassed}`);
  console.log(`Total Failed: ${totalFailed}`);
  console.log(`Success Rate: ${((totalPassed / (totalPassed + totalFailed)) * 100).toFixed(1)}%`);
  
  // Save report to file
  const reportData = {
    timestamp: new Date().toISOString(),
    summary: {
      totalPassed,
      totalFailed,
      successRate: ((totalPassed / (totalPassed + totalFailed)) * 100).toFixed(1)
    },
    results: validationResults
  };
  
  fs.writeFileSync('tests/mobile-validation-report.json', JSON.stringify(reportData, null, 2));
  console.log('\n📄 Detailed report saved to: tests/mobile-validation-report.json');
  
  return reportData;
}

/**
 * Main Validation Runner
 */
function runMobileValidation() {
  console.log('🚀 Starting Mobile Implementation Validation...\n');
  
  validateTouchInteractions();
  validateResponsiveDesign();
  validateLoadingStates();
  validateVisualFeedback();
  validateAccessibility();
  validateTranslations();
  
  return generateValidationReport();
}

// Run validation if called directly
if (require.main === module) {
  runMobileValidation();
}

module.exports = {
  runMobileValidation,
  validateTouchInteractions,
  validateResponsiveDesign,
  validateLoadingStates,
  validateVisualFeedback,
  validateAccessibility,
  validateTranslations
};