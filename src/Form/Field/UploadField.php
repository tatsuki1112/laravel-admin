<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait UploadField
{
    /**
     * Upload directory.
     *
     * @var \Closure|string|null
     */
    protected $directory = '';

    /**
     * File name.
     * @var string|null|mixed
     */
    protected $name = null;

    /**
     * callable name.
     * @var callable|null
     */
    protected $callableName = null;

    /**
     * Storage instance.
     * @var string|FilesystemAdapter
     */
    protected $storage = '';

    /**
     * If use unique name to store upload file.
     *
     * @var bool
     */
    protected $useUniqueName = false;

    /**
     * If use sequence name to store upload file.
     *
     * @var bool
     */
    protected $useSequenceName = false;

    /**
     * Retain file when delete record from DB.
     *
     * @var bool
     */
    protected $retainable = false;

    /**
     * @var bool
     */
    protected $downloadable = true;

    /**
     * Configuration for setting up file actions for newly selected file thumbnails in the preview window.
     *
     * @var array<string,bool>
     */
    protected $fileActionSettings = [
        'showRemove' => false,
        'showDrag'   => false,
    ];

    /**
     * Controls the storage permission. Could be 'private' or 'public'.
     *
     * @var string|null
     */
    protected $storagePermission;

    /**
     * filetype
     *
     * @var string|null
     */
    protected $filetype;

    /**
     * Get file from tmp. Almost use preview.
     *
     * @var \Closure|null
     */
    protected $getTmp = null;

    /**
     * @var array<string, string>
     */
    protected $fileTypes = [
        'image'  => '/^(gif|png|jpe?g|svg)$/i',
        'html'   => '/^(htm|html)$/i',
        'office' => '/^(docx?|xlsx?|pptx?|pps|potx?)$/i',
        'gdocs'  => '/^(docx?|xlsx?|pptx?|pps|potx?|rtf|ods|odt|pages|ai|dxf|ttf|tiff?|wmf|e?ps)$/i',
        //'text'   => '/^(txt|md|csv|nfo|ini|json|php|js|css|ts|sql)$/i',
        'video'  => '/^(og?|mp4|webm|mp?g|mov|3gp)$/i',
        'audio'  => '/^(og?|mp3|mp?g|wav)$/i',
        'pdf'    => '/^(pdf)$/i',
        'flash'  => '/^(swf)$/i',
    ];

    /**
     * Initialize the storage instance.
     *
     * @return void.
     */
    protected function initStorage()
    {
        $this->disk(config('admin.upload.disk'));
    }

    /**
     * Set filetype
     * @param string  $filetype
     *
     * @return $this
     */
    public function filetype($filetype)
    {
        $this->filetype = $filetype;

        return $this;
    }

    /**
     * Set get file from tmp. Almost use preview.
     *
     * @param  \Closure  $getTmp  Get file from tmp. Almost use preview.
     *
     * @return  self
     */ 
    public function getTmp(\Closure $getTmp)
    {
        $this->getTmp = $getTmp;

        return $this;
    }

    /**
     * Set default options form image field.
     *
     * @return void
     */
    protected function setupDefaultOptions()
    {
        $defaults = [
            'overwriteInitial'     => false,
            'initialPreviewAsData' => true,
            'browseLabel'          => trans('admin.browse'),
            'cancelLabel'          => trans('admin.cancel'),
            'showRemove'           => false,
            'showUpload'           => false,
            'showCancel'           => false,
            'dropZoneEnabled'      => false,
            'preferIconicPreview'  => true,
            'allowedPreviewTypes'  => ['image'],
            'previewFileIcon' => '<i class="fa fa-file"></i>',
            'previewFileIconSettings' => array(
                'txt' => '<i class="fa fa-file text-primary"></i>',
                'xml' => '<i class="fa fa-file text-primary"></i>',
                'pdf' => '<i class="fa fa-file-pdf-o text-primary"></i>',
                'doc' => '<i class="fa fa-file-word-o text-primary"></i>',
                'docx' => '<i class="fa fa-file-word-o text-primary"></i>',
                'docm' => '<i class="fa fa-file-word-o text-primary"></i>',
                'xls' => '<i class="fa fa-file-excel-o text-success"></i>',
                'xlsx' => '<i class="fa fa-file-excel-o text-success"></i>',
                'xlsm' => '<i class="fa fa-file-excel-o text-success"></i>',
                'ppt' => '<i class="fa fa-file-powerpoint-o text-danger"></i>',
                'pptx' => '<i class="fa fa-file-powerpoint-o text-danger"></i>',
                'pptm' => '<i class="fa fa-file-powerpoint-o text-danger"></i>',
                'zip' => '<i class="fa fa-file-archive-o text-muted"></i>',
            ),
            'deleteExtraData'      => [
                $this->formatName($this->column) => static::FILE_DELETE_FLAG,
                static::FILE_DELETE_FLAG         => '',
                '_token'                         => csrf_token(),
                '_method'                        => 'PUT',
            ],
        ];

        if ($this->form instanceof Form) {
            $defaults['deleteUrl'] = $this->form->resource().'/'.$this->form->model()->getKey();
        }

        $defaults = array_merge($defaults, ['fileActionSettings' => $this->fileActionSettings]);

        $this->options($defaults);
    }

    /**
     * Set preview options form image field.
     *
     * @return void
     */
    protected function setupPreviewOptions()
    {
        $initialPreviewConfig = $this->initialPreviewConfig();

        $this->options(compact('initialPreviewConfig'));
    }

    /**
     * @param string $file
     *
     * @return array<string>|bool
     */
    protected function guessPreviewType($file)
    {
        $ext = '';
        if(!is_null($this->filetype)){
            $filetype = $this->filetype;
        }
        else{
            $filetype = 'other';
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
            foreach ($this->fileTypes as $type => $pattern) {
                if (preg_match($pattern, $ext) === 1) {
                    $filetype = $type;
                    break;
                }
            }    
        }

        $extra = ['type' => $filetype];

        if ($filetype == 'video') {
            $extra['filetype'] = "video/{$ext}";
        }

        if ($this->downloadable) {
            $extra['downloadUrl'] = $this->objectUrl($file);
        }

        return $extra;
    }

    /**
     * Indicates if the underlying field is downloadable.
     *
     * @param bool $downloadable
     *
     * @return $this
     */
    public function downloadable($downloadable = true)
    {
        $this->downloadable = $downloadable;

        return $this;
    }

    /**
     * Allow use to remove file.
     *
     * @return $this
     */
    public function removable()
    {
        $this->fileActionSettings['showRemove'] = true;

        return $this;
    }

    /**
     * Indicates if the underlying field is retainable.
     * @param bool $retainable
     *
     * @return $this
     */
    public function retainable($retainable = true)
    {
        $this->retainable = $retainable;

        return $this;
    }

    /**
     * Set options for file-upload plugin.
     *
     * @param array<mixed> $options
     *
     * @return $this
     */
    public function options($options = [])
    {
        $this->options = array_merge($options, $this->options);

        return $this;
    }

    /**
     * Set disk for storage.
     *
     * @param string $disk Disks defined in `config/filesystems.php`.
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function disk($disk)
    {
        try {
            $this->storage = Storage::disk($disk);
        } catch (\Exception $exception) {
            if (!array_key_exists($disk, config('filesystems.disks'))) {
                admin_error(
                    'Config error.',
                    "Disk [$disk] not configured, please add a disk config in `config/filesystems.php`."
                );

                return $this;
            }

            throw $exception;
        }

        return $this;
    }

    /**
     * Specify the directory and name for upload file.
     *
     * @param string      $directory
     * @param null|string $name
     *
     * @return $this
     */
    public function move($directory, $name = null)
    {
        $this->dir($directory);

        $this->name($name);

        return $this;
    }

    /**
     * Specify the directory upload file.
     *
     * @param string $dir
     *
     * @return $this
     */
    public function dir($dir)
    {
        if ($dir) {
            $this->directory = $dir;
        }

        return $this;
    }

    /**
     * Set name of store name.
     *
     * @param string|callable $name
     *
     * @return $this
     */
    public function name($name)
    {
        if ($name) {
            $this->name = $name;
        }

        return $this;
    }

    /**
     * Set callable name.
     *
     * @param callable|null $callableName
     * @return $this
     */
    public function callableName($callableName)
    {
        if ($callableName) {
            $this->callableName = $callableName;
        }

        return $this;
    }

    /**
     * Use unique name for store upload file.
     *
     * @return $this
     */
    public function uniqueName()
    {
        $this->useUniqueName = true;

        return $this;
    }

    /**
     * Use sequence name for store upload file.
     *
     * @return $this
     */
    public function sequenceName()
    {
        $this->useSequenceName = true;

        return $this;
    }

    /**
     * Get store name of upload file.
     *
     * @param UploadedFile|null $file
     *
     * @return string|null
     */
    protected function getStoreName(?UploadedFile $file)
    {
        if(is_null($file)){
            return null;
        }
        
        if ($this->useUniqueName) {
            return $this->generateUniqueName($file);
        }

        if ($this->useSequenceName) {
            return $this->generateSequenceName($file);
        }

        if ($this->name instanceof \Closure) {
            return $this->name->call($this, $file, $this);
        }

        if ($this->callableName instanceof \Closure) {
            return $this->callableName->call($this, $file, $this);
        }

        if (is_string($this->name)) {
            return $this->name;
        }

        return $file->getClientOriginalName();
    }

    /**
     * Get directory for store file.
     *
     * @return mixed|string
     */
    public function getDirectory()
    {
        if ($this->directory instanceof \Closure) {
            return call_user_func($this->directory, $this->form);
        }

        return $this->directory ?: $this->defaultDirectory();
    }

    /**
     * Upload file and delete original file.
     *
     * @param UploadedFile $file
     *
     * @return mixed
     */
    protected function upload(UploadedFile $file)
    {
        $this->renameIfExists($file);

        if (!is_null($this->storagePermission)) {
            return $this->storage->putFileAs($this->getDirectory(), $file, $this->name, $this->storagePermission);
        }

        return $this->storage->putFileAs($this->getDirectory(), $file, $this->name);
    }

    /**
     * If name already exists, rename it.
     *
     * @param $file
     *
     * @return void
     */
    public function renameIfExists(UploadedFile $file)
    {
        if ($this->storage->exists("{$this->getDirectory()}/$this->name")) {
            $this->name = $this->generateUniqueName($file);
        }
    }

    /**
     * Get file visit url.
     *
     * @param string $path
     *
     * @return string
     */
    public function objectUrl($path)
    {
        if (URL::isValidUrl($path)) {
            return $path;
        }

        if ($this->storage) {
            return $this->storage->url($path);
        }

        return Storage::disk(config('admin.upload.disk'))->url($path);
    }

    /**
     * Generate a unique name for uploaded file.
     *
     * @param UploadedFile $file
     *
     * @return string
     */
    protected function generateUniqueName(UploadedFile $file)
    {
        return md5(uniqid()).'.'.$file->getClientOriginalExtension();
    }

    /**
     * Generate a sequence name for uploaded file.
     *
     * @param UploadedFile $file
     *
     * @return string
     */
    protected function generateSequenceName(UploadedFile $file)
    {
        $index = 1;
        $extension = $file->getClientOriginalExtension();
        $original = $file->getClientOriginalName();
        $new = sprintf('%s_%s.%s', $original, $index, $extension);

        while ($this->storage->exists("{$this->getDirectory()}/$new")) {
            $index++;
            $new = sprintf('%s_%s.%s', $original, $index, $extension);
        }

        return $new;
    }

    /**
     * Destroy original files.
     *
     * @return void.
     */
    public function destroy()
    {
        if ($this->retainable) {
            return;
        }

        if (method_exists($this, 'destroyThumbnail')) {
            $this->destroyThumbnail();
        }

        if(!$this->original){
            return;
        }

        if ($this->storage->exists($this->original)) {
            $this->storage->delete($this->original);
        }
    }

    /**
     * Set file permission when stored into storage.
     *
     * @param string $permission
     *
     * @return $this
     */
    public function storagePermission($permission)
    {
        $this->storagePermission = $permission;

        return $this;
    }
}
