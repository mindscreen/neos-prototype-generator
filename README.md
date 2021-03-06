# Mindscreen.Neos.PrototypeGenerator
This package provides a default prototype generator for creating fusion component-based prototypes to enable front-end developers to create a new node-type based on a yaml-configuration and a view-component without worrying about basic property-mapping.

## Installation
`composer require mindscreen/neos-prototype-generator`

## Usage
* Extend your new node-type from `Mindscreen.Neos:ContentComponent` (or include the `'Mindscreen.Neos:PrototypeGeneratorMixin'`).
* Inline-editable properties are recognized with `type: string` and `ui.inlineEditable: true`. Block-editing can be enabled with `ui.inline.editorOptions.multiLine: true`.
* Images (`Neos\Media\Domain\Model\ImageInterface`) provide a `<property>Uri` and `<property>Asset`.
* Child-nodes will render with their specific node as `node` in the context; `Neos.Neos:ContentCollection` will render with the respective `nodePath`
* Set a specific target-component prototype in `options.componentName`.
* Specify the child-node mapping in `options.componentMapping.childNodes.<nodeName>`

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
    'Neos.Neos:Content': true
    'Mindscreen.Neos:PrototypeGeneratorMixin': true
  childNodes:
    main:
      type: 'Neos.Neos:ContentCollection'
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
    componentMapping:
      childNodes:
        main: content
```

## Notes
### Content-Collections
If a node-type is a `Neos.Neos:ContentCollection` (e.g. to generate a simple box-component), a ContentCollection will be rendered as `content`. This can be configured with the option `options.componentMapping.childNodes.this`.
