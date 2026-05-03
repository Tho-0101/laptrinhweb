# CSS Organization

## File Structure:

### Core Styles:
- **style.css** - Main stylesheet với global styles, variables, common components

### Page-Specific Styles:
- **auth.css** - Login & Register pages
- **list-page-styles.css** - Bikes listing page
- **header-styles.css** - Main header component (if using header.php)

### Feature-Specific Styles:
- **dashboard.css** - Dashboard layouts (buyer, seller, admin, inspector)
- **forms.css** - Form components (post-bike, checkout, etc)
- **cards.css** - Bike cards, order cards, etc

## Usage:

### In HTML <head>:
```html
<!-- Core styles (always include) -->
<link href="../../assets/css/style.css?v=4.0" rel="stylesheet">

<!-- Page-specific styles (include as needed) -->
<link href="../../assets/css/auth.css" rel="stylesheet">
<link href="../../assets/css/list-page-styles.css" rel="stylesheet">
```

## Best Practices:
- Always include style.css first
- Use CSS variables from style.css
- Keep page-specific styles in separate files
- Use semantic class names
- Comment your CSS sections
