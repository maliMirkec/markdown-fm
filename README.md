# Markdown FM

Add custom fields to your templates using Markdown Frontmatter.

markdown-fm/
├─ markdown-fm.php          <-- main plugin loader
├─ includes/
│  ├─ class-plugin.php      <-- initializes everything
│  ├─ class-field-manager.php  <-- handles field definitions & rendering
│  ├─ class-admin-page.php     <-- admin menu, template list, schema editor
│  └─ class-meta-box.php       <-- renders meta box on pages/posts
└─ vendor/                  <-- composer dependencies (Symfony YAML)
