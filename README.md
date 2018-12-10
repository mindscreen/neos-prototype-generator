# Mindscreen.Neos.PrototypeGenerator
This package provides a default prototype generator for creating fusion component-based prototypes to enable front-end developers to create a new node-type based on a yaml-configuration and a view-component without worrying about basic property-mapping.

## Installation
`composer require mindscreen/neos-prototype-generator`

## Usage
* Extend your new node-type from `Mindscreen.Neos:ContentComponent` (or include the `'Mindscreen.Neos:PrototypeGeneratorMixin'`).
* Inline-editable properties are recognized with `type: string` and `ui.inlineEditable: true`. Block-editing can be enabled with `ui.inline.editorOptions.multiLine: true`.
* Images (`Neos\Media\Domain\Model\ImageInterface`) provide a `<property>Uri` and `<property>Asset`.
* Set a specific target-component prototype in `options.componentName`.

## Configuration
Settings:
```yaml
Mindscreen:
  Neos:
    PrototypeGenerator:
      componentPatterns:
        - '<package>:Component.Atom.<nodetype>'
        - '<package>:Component.Molecule.<nodetype>'
        - '<package>:Component.Organism.<nodetype>'
      superType: 'Neos.Neos:ContentComponent'
```

Usage in node-types:
```yaml
'Vendor.Package:Example':
  superTypes:
    'Mindscreen.Neos:ContentComponent': true
  properties:
    singleLine:
      type: string
      ui:
        inlineEditable: true
        inline:
          editorOptions:
            placeholder: singleline
    multiLine:
      type: string
      ui:
        inlineEditable: true
        inline:
          editorOptions:
            placeholder: multiline
            multiLine: true
  options:
    componentName: 'Vendor.Package:Components.General.Example'
```

