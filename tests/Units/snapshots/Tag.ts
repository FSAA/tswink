import type { SetRequired } from '@universite-laval/script-components'
import type BaseModel from './BaseModel'
import type TestClass from './TestClass'
import type TestClassTagPivot from './TestClassTagPivot'

// <non-auto-generated-import-declarations>

// </non-auto-generated-import-declarations>

export default interface Tag extends BaseModel {
    assignment?: SetRequired<TestClassTagPivot, 'priority'>;
    created_at?: string;
    id?: number;
    name?: string;
    test_classes?: SetRequired<TestClass, 'assignment'>[];
    updated_at?: string;

    // <non-auto-generated-class-declarations>

    // </non-auto-generated-class-declarations>
}

// <non-auto-generated-code>

// </non-auto-generated-code>

export const TagConstants = {
    phpQualifiedClassName: 'TsWinkTests\\Units\\Input\\Tag',
}
