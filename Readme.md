# GridField Bulk Delete

## Introduction

Add a `GridFieldBulkDeleteForm` component to a gridfield config to add an option to delete the records within that list.

## Requirements
* [silverstripe/framework](https://github.com/silverstripe/framework)

## Install

```
composer require heyday/gridfield-bulk-delete
```

## Configuration

### Add to gridfield config

```
$config->addComponent(new GridFieldBulkDeleteForm('buttons-after-right'));
```

Forked from: [dnadesign/gridfield-bulk-delete](https://github.com/dnadesign/gridfield-bulk-delete)