# shadcn/ui Design System Documentation

## Overview

The Central SSO server implements a custom shadcn/ui-inspired design system built with Tailwind CSS and Alpine.js. This design system provides a consistent, modern, and accessible user interface across all admin interfaces and authentication flows.

## 🎨 Design Philosophy

### Core Principles

1. **Consistency**: Unified visual language across all components
2. **Accessibility**: WCAG compliant with proper color contrast and semantic HTML
3. **Dark Mode Support**: Complete dark/light theme system with user preference persistence
4. **Modern Aesthetics**: Clean, minimal design with subtle shadows and smooth transitions
5. **Responsive Design**: Mobile-first approach with responsive breakpoints
6. **Performance**: Optimized for fast loading and smooth interactions

### Visual Hierarchy

- **Primary Colors**: Blue gradient (`blue-600` to `purple-600`) for branding
- **Semantic Colors**: Green for success, red for destructive actions, yellow for warnings
- **Neutral Palette**: Gray scale for text, borders, and backgrounds
- **Typography**: Clean, readable fonts with proper spacing and contrast

## 🛠️ Technical Implementation

### Tech Stack

```javascript
// Core Technologies
- Tailwind CSS (CDN) - Utility-first CSS framework
- Alpine.js (CDN) - Lightweight JavaScript framework
- Custom shadcn/ui theme configuration
- CSS custom properties for theme variables
```

### Color System

```javascript
// shadcn/ui Color Palette (Tailwind Config)
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                // Base colors
                border: "hsl(214.3 31.8% 91.4%)",
                input: "hsl(214.3 31.8% 91.4%)",
                ring: "hsl(222.2 84% 4.9%)",
                background: "hsl(0 0% 100%)",
                foreground: "hsl(222.2 84% 4.9%)",
                
                // Component colors
                primary: {
                    DEFAULT: "hsl(222.2 47.4% 11.2%)",
                    foreground: "hsl(210 40% 98%)",
                },
                secondary: {
                    DEFAULT: "hsl(210 40% 96%)",
                    foreground: "hsl(222.2 84% 4.9%)",
                },
                destructive: {
                    DEFAULT: "hsl(0 84.2% 60.2%)",
                    foreground: "hsl(210 40% 98%)",
                },
                muted: {
                    DEFAULT: "hsl(210 40% 96%)",
                    foreground: "hsl(215.4 16.3% 46.9%)",
                },
                accent: {
                    DEFAULT: "hsl(210 40% 96%)",
                    foreground: "hsl(222.2 84% 4.9%)",
                },
                popover: {
                    DEFAULT: "hsl(0 0% 100%)",
                    foreground: "hsl(222.2 84% 4.9%)",
                },
                card: {
                    DEFAULT: "hsl(0 0% 100%)",
                    foreground: "hsl(222.2 84% 4.9%)",
                },
            },
            borderRadius: {
                lg: "var(--radius)",
                md: "calc(var(--radius) - 2px)",
                sm: "calc(var(--radius) - 4px)",
            },
        }
    }
}
```

### CSS Variables

```css
:root {
    --radius: 0.5rem; /* Base border radius */
}
```

## 🌓 Dark Mode System

### Implementation

The app uses a sophisticated dark mode system with:

- **System Preference Detection**: Automatically detects user's OS theme preference
- **Persistent Storage**: Saves theme choice in localStorage
- **Smooth Transitions**: 300ms duration transitions for all color changes
- **Alpine.js Integration**: Reactive theme switching with `x-data="{ darkMode: $persist(false) }"`

### Theme Toggle Component

```html
<!-- Theme Toggle Button -->
<button @click="darkMode = !darkMode" 
        class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200"
        :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
    <svg x-show="!darkMode" class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
        <!-- Sun icon -->
    </svg>
    <svg x-show="darkMode" x-cloak class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
        <!-- Moon icon -->
    </svg>
</button>
```

### Dark Mode Classes Pattern

```html
<!-- Consistent dark mode pattern -->
<div class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 transition-colors duration-300">
    Content
</div>
```

## 📱 Component Library

### 1. Layout Components

#### Admin Layout
```html
<!-- Main layout structure -->
<div class="flex min-h-screen">
    <!-- Fixed sidebar (w-64) -->
    <div class="hidden w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 lg:block fixed h-screen">
        <!-- Navigation content -->
    </div>
    
    <!-- Main content area (ml-64 on large screens) -->
    <div class="flex-1 flex flex-col lg:ml-64">
        <!-- Header -->
        <header class="h-16 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <!-- Header content -->
        </header>
        
        <!-- Page content -->
        <main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 p-6">
            <!-- Page content -->
        </main>
    </div>
</div>
```

### 2. Navigation Components

#### Sidebar Navigation
```html
<!-- Navigation link with active state -->
<a href="{{ route('dashboard') }}" 
   class="@if(request()->routeIs('dashboard')) 
            bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100 
          @else 
            text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-100 
          @endif 
          group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200">
    <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <!-- Icon -->
    </svg>
    Dashboard
</a>
```

#### Tab Navigation
```html
<!-- Tab navigation with Alpine.js -->
<div class="border-b border-gray-200 dark:border-gray-700">
    <nav class="-mb-px flex space-x-8">
        <button @click="activeTab = 'roles'" 
                :class="activeTab === 'roles' ? 
                    'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400' : 
                    'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
            Roles (<span x-text="roles.length"></span>)
        </button>
    </nav>
</div>
```

### 3. Button Components

#### Primary Button
```html
<button class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-blue-600 dark:bg-blue-500 text-white hover:bg-blue-700 dark:hover:bg-blue-600 h-10 px-4 py-2">
    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <!-- Icon -->
    </svg>
    Button Text
</button>
```

#### Secondary Button
```html
<button class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-9 px-3">
    Edit
</button>
```

#### Destructive Button
```html
<button class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-600 hover:text-white dark:hover:bg-red-600 h-9 px-3">
    Delete
</button>
```

### 4. Card Components

#### Basic Card
```html
<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
    <div class="p-6">
        <!-- Card content -->
    </div>
</div>
```

#### Statistics Card
```html
<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm transition-colors duration-200">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center">
                <!-- Icon -->
            </div>
        </div>
        <div class="ml-5 w-0 flex-1">
            <dl>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                    Label
                </dt>
                <dd class="text-lg font-medium text-gray-900 dark:text-white">
                    Value
                </dd>
            </dl>
        </div>
    </div>
</div>
```

### 5. Form Components

#### Input Field
```html
<div class="space-y-2">
    <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-900 dark:text-white">
        Email
    </label>
    <input type="email" 
           class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 text-gray-900 dark:text-white">
</div>
```

#### Search Input
```html
<div class="relative">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <!-- Search icon -->
        </svg>
    </div>
    <input type="text" 
           placeholder="Search..."
           class="block w-full pl-10 pr-24 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md text-sm placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
</div>
```

### 6. Badge Components

#### Status Badge
```html
<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20">
    System
</span>
```

#### Count Badge
```html
<span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold border-transparent bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200">
    5 permissions
</span>
```

#### Permission Badge
```html
<span class="inline-flex items-center rounded-md bg-blue-100 dark:bg-blue-900 px-2 py-1 text-xs font-medium text-blue-800 dark:text-blue-200 ring-1 ring-inset ring-blue-200 dark:ring-blue-700">
    users.view
</span>
```

### 7. Table Components

#### Responsive Table
```html
<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
    <div class="overflow-hidden">
        <table class="w-full caption-bottom text-sm">
            <thead class="border-b border-gray-200 dark:border-gray-700">
                <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400">
                        Column Header
                    </th>
                </tr>
            </thead>
            <tbody class="border-0">
                <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="p-4 align-middle text-gray-900 dark:text-white">
                        Cell Content
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

### 8. Modal Components

#### Confirmation Modal
```html
<div x-show="showModal" x-cloak class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50" x-transition>
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-card">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-destructive/10">
                <svg class="h-6 w-6 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <!-- Warning icon -->
                </svg>
            </div>
            <h3 class="text-lg font-medium text-card-foreground mt-2">Confirm Action</h3>
            <p class="text-sm text-muted-foreground mt-2">Are you sure you want to proceed?</p>
            <div class="flex gap-3 justify-center mt-6">
                <button class="px-4 py-2 bg-destructive text-destructive-foreground text-sm font-medium rounded-md">
                    Confirm
                </button>
                <button class="px-4 py-2 bg-secondary text-secondary-foreground text-sm font-medium rounded-md">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
```

### 9. Toast Notification System

#### Toast Implementation
```javascript
// Toast notification function
window.showToast = function(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    
    // Toast styling based on type
    let iconColor, bgColor;
    switch (type) {
        case 'success':
            iconColor = 'text-green-600';
            bgColor = 'bg-green-50 border-green-200';
            break;
        case 'error':
            iconColor = 'text-destructive';
            bgColor = 'bg-destructive/10 border-destructive/20';
            break;
        // ... other types
    }
    
    toast.className = `min-w-80 max-w-lg w-full bg-card border border-border rounded-lg shadow-lg transform transition-all duration-300`;
    // ... implementation
};
```

#### Toast Usage
```javascript
// Show success toast
showToast('User created successfully!', 'success');

// Show error toast
showToast('Failed to delete user', 'error');

// Show warning toast
showToast('This action cannot be undone', 'warning');

// Show info toast
showToast('Processing your request...', 'info');
```

## 🎯 Design Patterns

### 1. Color Usage Patterns

#### Semantic Colors
- **Blue**: Primary actions, links, active states
- **Green**: Success messages, completed states
- **Red**: Errors, destructive actions, warnings
- **Yellow**: Warnings, pending states
- **Gray**: Text, borders, neutral elements

#### Dark Mode Color Mapping
```css
/* Light mode → Dark mode mapping */
white → gray-800
gray-50 → gray-900
gray-100 → gray-700
gray-600 → gray-400
gray-900 → white
```

### 2. Spacing and Layout

#### Container Patterns
```html
<!-- Page container -->
<div class="space-y-6"> <!-- 24px vertical spacing -->
    
<!-- Card content -->
<div class="p-6"> <!-- 24px padding -->

<!-- Form spacing -->
<div class="space-y-4"> <!-- 16px vertical spacing -->

<!-- Button groups -->
<div class="flex space-x-3"> <!-- 12px horizontal spacing -->
```

#### Responsive Breakpoints
- **sm**: 640px and up
- **md**: 768px and up  
- **lg**: 1024px and up (sidebar becomes fixed)
- **xl**: 1280px and up
- **2xl**: 1536px and up

### 3. Typography Scale

```html
<!-- Headers -->
<h1 class="text-2xl font-semibold"> <!-- Page titles -->
<h2 class="text-lg font-semibold">   <!-- Section titles -->
<h3 class="text-lg font-medium">     <!-- Card titles -->

<!-- Body text -->
<p class="text-sm text-gray-600 dark:text-gray-400"> <!-- Descriptions -->
<span class="text-xs font-medium">                    <!-- Labels -->

<!-- Code/monospace -->
<code class="text-xs font-mono text-gray-600 dark:text-gray-400">
```

### 4. Interactive States

#### Hover States
```html
class="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200"
```

#### Focus States
```html
class="focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
```

#### Active States
```html
class="bg-blue-100 dark:bg-blue-900 text-blue-900 dark:text-blue-100"
```

## 🔧 Customization Guide

### Adding New Components

1. **Follow shadcn/ui Patterns**: Use existing component classes as templates
2. **Include Dark Mode**: Always add dark: variants for colors
3. **Add Transitions**: Include `transition-colors duration-200` for smooth animations
4. **Ensure Accessibility**: Include proper focus states and semantic HTML

### Custom Color Variants

```javascript
// Add custom colors to Tailwind config
colors: {
    brand: {
        50: '#eff6ff',
        500: '#3b82f6',
        900: '#1e3a8a',
    }
}
```

### Component Variants

```html
<!-- Size variants -->
class="h-8 px-3 text-xs"  <!-- Small -->
class="h-10 px-4 text-sm" <!-- Medium (default) -->
class="h-12 px-6 text-base" <!-- Large -->

<!-- Color variants -->
class="bg-blue-600 text-white"     <!-- Primary -->
class="bg-gray-100 text-gray-900"  <!-- Secondary -->
class="bg-red-600 text-white"      <!-- Destructive -->
```

## 📚 Best Practices

### 1. Consistency Guidelines

- **Always use semantic color classes**: `text-destructive` instead of `text-red-600`
- **Include dark mode variants**: Every color should have a dark: variant
- **Use consistent spacing**: Follow the space-y-* and space-x-* patterns
- **Maintain button patterns**: Use established button component classes

### 2. Accessibility Standards

- **Color Contrast**: Ensure 4.5:1 contrast ratio for normal text
- **Focus Indicators**: Always include visible focus states
- **Semantic HTML**: Use proper HTML elements (button, nav, main, etc.)
- **Screen Reader Support**: Include aria-labels and proper headings

### 3. Performance Optimization

- **CDN Usage**: Uses Tailwind and Alpine.js from CDN for fast loading
- **CSS Purging**: Only includes used classes in production
- **Transition Optimization**: Limited to color transitions for smooth performance
- **JavaScript Minimization**: Alpine.js provides reactivity with minimal overhead

### 4. Maintenance

- **Component Reusability**: Extract common patterns into reusable components
- **Documentation Updates**: Keep this documentation current with changes
- **Version Consistency**: Maintain consistent CDN versions across all views
- **Testing**: Test all components in both light and dark modes

## 🚀 Future Enhancements

### Planned Improvements

1. **Component Library Extraction**: Move common components to separate files
2. **CSS Custom Properties**: Expand use of CSS variables for easier theming
3. **Animation Library**: Add micro-interactions and loading states
4. **Form Validation**: Enhanced form styling with validation states
5. **Mobile Optimization**: Improved mobile navigation and responsive components

### Integration Opportunities

1. **Build Process**: Integrate with Laravel Mix or Vite for asset compilation
2. **Component Testing**: Add visual regression testing for components
3. **Design Tokens**: Implement design token system for consistent styling
4. **Storybook Integration**: Create component documentation and playground