<?php
/**
 * @author Dmitriy Kachurovskiy <kachurovskiyds@gmail.com>
 */

namespace WHMCS\Module\Blazing\Export\GetResponse\Compiler;

use Illuminate\Database\Query\Builder;
use WHMCS\Module\Blazing\Export\Compiler\Compiler;
use WHMCS\Module\Blazing\Export\GetResponse\Client\Client;
use WHMCS\Module\Blazing\Export\GetResponse\ClientContext;

class CompilerMergeDecorator
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Builder
     */
    private $table;

    /**
     * @var Compiler
     */
    private $delegate;

    public function __construct(Compiler $delegate, Client $client, callable $table)
    {
        $this->client = $client;
        $this->table = $table;
        $this->delegate = $delegate;
    }

    /**
     * @param ClientContext $context
     *
     * @return array
     */
    public function compile($context)
    {
        $compiled = $this->delegate->compile($context);
        $user = call_user_func($this->table)
            ->where('client_id', $context->getClientModel()->id)
            ->first();

        if (!$user || !($contactId = $user['getresponse_id'])) {
            return $compiled;
        }
        $response = $this->client->getContact($contactId);

        if (!$response->isSuccess()) {
            return $compiled;
        }

        $data = $response->getResult();
        $mergedData = $this->mergeData($data, $compiled);
        return $mergedData;
    }

    public function mergeData(array $receivedData, array $compiledData)
    {
        $mergedData = $this->mergeSimpleFields(
            $receivedData, $compiledData, ['email', 'ip', 'name']
        );

        $receivedFields = isset($receivedData['customFieldValues'])
            ? $receivedData['customFieldValues']
            : [];
        $compiledFields = isset($compiledData['customFieldValues'])
            ? $compiledData['customFieldValues']
            : [];
        if ($receivedFields && $compiledFields) {
            $mergedData['customFieldValues'] = $this->mergeCustomFields(
                $receivedFields, $compiledFields
            );
        }

        $receivedTags = isset($receivedData['tags'])
            ? $receivedData['tags']
            : [];
        $receivedTags = array_map(
            function ($tag) {
                return array_only($tag, 'tagId');
            },
            $receivedTags
        );
        $receivedTags = array_filter($receivedTags, function ($v) {
            return !empty($v);
        });
        $compiledTags = isset($compiledData['tags'])
            ? $compiledData['tags']
            : [];
        if ($receivedTags || $compiledTags) {
            $receivedTagsMap = array_column($receivedTags, 'tagId');
            $compiledTagsMap = array_column($compiledTags, 'tagId');
            $tagDiffMap = array_diff($compiledTagsMap, $receivedTagsMap);
            if ($tagDiffMap) {
                $newCompiled = array_filter($compiledTags, function ($tag) use ($tagDiffMap) {
                    return in_array(isset($tag['tagId']) ? $tag['tagId'] : null, $tagDiffMap);
                });
                $tags = array_merge($receivedTags, $newCompiled);
                $mergedData['tags'] = $tags;
            }
        }

        return $mergedData;
    }

    public function mergeSimpleFields(
        $receivedData,
        $compiledData,
        $simpleFields
    )
    {
        $merged = [];
        foreach ($simpleFields as $field) {
            $compiledField = isset($compiledData[$field])
                ? $compiledData[$field] : null;
            $receivedField = isset($receivedData[$field])
                ? $receivedData[$field] : null;
            if ($receivedField != $compiledField) {
                $merged[$field] = $compiledField;
            }
        }
        return $merged;
    }

    public function mergeCustomFields($receivedFields, $compiledFields)
    {
        $customFieldCmp = function ($l, $r) {
            strcmp(
                isset($l['customFieldId']) ? $l['customFieldId'] : '',
                isset($r['customFieldId']) ? $r['customFieldId'] : ''
            );
        };
        $receivedFields = array_map(
            function ($field) {
                return array_only($field, ['customFieldId', 'value']);
            },
            $receivedFields
        );

        $intersect = array_uintersect(
            $compiledFields,
            $receivedFields,
            $customFieldCmp
        );
        $intersectLookup = array_column($intersect, 'customFieldId');

        $diff = array_filter(
            $receivedFields,
            function ($field) use ($intersectLookup) {
                return isset($field['customFieldId']) && !in_array($field['customFieldId'], $intersectLookup);
            }
        );
        $merged = array_merge($diff, $intersect);

        return $merged;
    }
}
