<?php
/**
 * DEMONSTRATION: Flexible RBAC System for Coffee Shop
 * 
 * This script demonstrates how easy it is to add new roles and permissions
 * without changing any code - everything is database-driven and dynamic.
 */

require 'vendor/autoload.php';

// This would normally be done through the admin panel or API
echo "🚀 FLEXIBLE RBAC SYSTEM DEMONSTRATION\n";
echo "=====================================\n\n";

echo "✅ CURRENT SYSTEM ROLES:\n";
echo "1. HR Central (Level 100) - Full access to everything\n";
echo "2. System Admin (Level 90) - Technical administration\n"; 
echo "3. Branch Manager (Level 80) - Multiple branches\n";
echo "4. Pengelola (Level 60) - Up to 3 outlets\n";
echo "5. Supervisor (Level 50) - Branch supervisor [CUSTOMIZABLE]\n";
echo "6. Shift Leader (Level 40) - Team leadership [CUSTOMIZABLE]\n";
echo "7. Senior Barista (Level 30) - Experienced staff [CUSTOMIZABLE]\n";
echo "8. Employee (Level 10) - Standard staff\n\n";

echo "💡 ADDING NEW ROLES IS SIMPLE:\n";
echo "==============================\n\n";

echo "Example 1: Adding 'Area Manager' for multiple regions:\n";
echo "------------------------------------------------------\n";
echo "POST /api/admin/roles\n";
echo "{\n";
echo '  "name": "area_manager",'. "\n";
echo '  "display_name": "Area Manager",'. "\n";
echo '  "description": "Manages multiple regions of coffee shops",'. "\n";
echo '  "color": "#8e44ad",'. "\n";
echo '  "hierarchy_level": 75,'. "\n";
echo '  "permissions": ['. "\n";
echo '    "branch.view.assigned",'. "\n";
echo '    "employee.view.branch",'. "\n";
echo '    "attendance.view.branch",'. "\n";
echo '    "report.view.branch",'. "\n";
echo '    "leave.approve.level2"'. "\n";
echo '  ]'. "\n";
echo "}\n\n";

echo "Example 2: Adding 'Trainee Barista' with limited access:\n";
echo "--------------------------------------------------------\n";
echo "POST /api/admin/roles\n";
echo "{\n";
echo '  "name": "trainee_barista",'. "\n";
echo '  "display_name": "Trainee Barista",'. "\n";
echo '  "description": "New barista in training period",'. "\n";
echo '  "color": "#16a085",'. "\n";
echo '  "hierarchy_level": 5,'. "\n";
echo '  "permissions": ['. "\n";
echo '    "attendance.view.own",'. "\n";
echo '    "schedule.view.own"'. "\n";
echo '  ]'. "\n";
echo "}\n\n";

echo "Example 3: Using Templates for Quick Setup:\n";
echo "-------------------------------------------\n";
echo "POST /api/admin/roles/template\n";
echo "{\n";
echo '  "template": "coffee_manager",'. "\n";
echo '  "name": "store_manager_jakarta"'. "\n";
echo "}\n";
echo "// Instantly creates a role with preset permissions!\n\n";

echo "🔧 PERMISSION EXAMPLES:\n";
echo "=======================\n";
echo "• attendance.view.all - View all attendance records\n";
echo "• attendance.view.branch - View branch attendance only\n";
echo "• attendance.view.own - View own attendance only\n";
echo "• schedule.create.branch - Create schedules for branch\n";
echo "• leave.approve.level1 - First level leave approval\n";
echo "• branch.view.assigned - View assigned branches only\n";
echo "• report.export.branch - Export branch reports\n";
echo "• system.role.manage - Manage roles (admin only)\n\n";

echo "🎯 SCOPE RESTRICTIONS:\n";
echo "======================\n";
echo "Roles can be restricted by:\n";
echo "• Specific branches: [1, 2, 5] (only these branch IDs)\n";
echo "• Date ranges: 2024-01-01 to 2024-12-31\n";
echo "• Employee groups: [10, 20, 30] (only these employee IDs)\n\n";

echo "📊 DASHBOARD CUSTOMIZATION:\n";
echo "===========================\n";
echo "Each role can have custom dashboard:\n";
echo "• Different widgets based on permissions\n";
echo "• Role-specific color themes\n";
echo "• Custom menu configurations\n";
echo "• Personalized layouts\n\n";

echo "🚀 REAL-TIME FEATURES:\n";
echo "======================\n";
echo "• Permissions are cached for performance\n";
echo "• Role changes take effect immediately\n";
echo "• Hierarchical approval chains\n";
echo "• Audit logging for all changes\n";
echo "• API-driven for frontend integration\n\n";

echo "✨ COFFEE SHOP SPECIFIC EXAMPLES:\n";
echo "=================================\n";
echo "1. 'Drive_Thru_Specialist' - Special role for drive-thru locations\n";
echo "2. 'Night_Shift_Manager' - For 24-hour locations\n";
echo "3. 'Regional_Trainer' - Travels between locations for training\n";
echo "4. 'Quality_Inspector' - Checks quality across multiple stores\n";
echo "5. 'Franchise_Owner' - Special permissions for franchise locations\n\n";

echo "🎉 BENEFITS:\n";
echo "============\n";
echo "✅ No code changes needed to add new roles\n";
echo "✅ Perfect for franchise/multi-location businesses\n";
echo "✅ Granular permission control\n";
echo "✅ Easy to manage through admin panel\n";
echo "✅ Role templates for quick setup\n";
echo "✅ Hierarchical approval workflows\n";
echo "✅ Audit trail for compliance\n";
echo "✅ Flexible scope restrictions\n\n";

echo "To add a new role in production:\n";
echo "1. Go to Admin Panel > Role Management\n";
echo "2. Click 'Add Custom Role' or 'Use Template'\n";
echo "3. Configure permissions and settings\n";
echo "4. Assign to users immediately!\n\n";

echo "🎯 The system is now 100% flexible and scalable! 🎯\n";
?>
